<?php

namespace bsctool;

use Exception;
use Web3\Contract;
use Web3\Utils;

class Transactor
{
    protected $web3;

    //context
    protected $gasPrice;
    protected $gasLimit;
    protected $value;
    protected $credential;
    protected $grade = 1;

    public function __construct ($web3, $credential) {
        $this->web3 = $web3;
        $this->credential = $credential;
    }


    public function setGasPrice ($price = null) {
        $this->gasPrice = $price;
        return $this;
    }


    public function setGrade ($num = 1) {
        $this->grade = $num;
        return $this;
    }

    public function setGasLimit ($limit = null) {
        $this->gasLimit = $limit;
        return $this;
    }

    public function setValue ($value = null) {
        $this->value = $value;
        return $this;
    }

    public function setCredential ($credential) {
        $this->credential = $credential;
        return $this;
    }

    protected function netVersion () {
        $cb = new Callback;
        $this->web3->net->version($cb);
        return $cb->result;
    }

    protected function getTransactionCount ($address) {
        $cb = new Callback;
        $this->web3->eth->getTransactionCount($address, 'pending', $cb);
        if(!$cb->result->toHex()){
            return '0x0';
        }
        return '0x' . $cb->result->toHex();
    }

    protected function estimateGasPrice () {
        $cb = new Callback;
        $this->web3->eth->gasPrice($cb);
        //  echo 'estimateGasPrice'.PHP_EOL;
        // var_dump($cb->result);
        return '0x' . $cb->result->toHex();
    }

    protected function estimateGasUsage ($tx) {
        //var_dump($tx);
        $cb = new Callback;
        // var_dump($this->web3->eth);
        $this->web3->eth->estimateGas($tx, $cb);
        //  echo 'estimateGasUsage'.PHP_EOL;
        //   var_dump($cb->result);
        return '0x' . $cb->result->toHex();
    }

    /**
     * 将一个十进制小数（可以是 float 或者 string）转换为最简分数
     * 并返回一个 ['numerator' => x, 'denominator' => y] 的数组。
     *
     * @param float|string $decimal 小数，例如 2.75、"0.125"、"-3.5"、"4" 等
     * @return array{numerator: int, denominator: int}
     */
    function decimalToFraction($decimal): array
    {
        // 1. 先把输入转成字符串，方便处理小数点
        $str = (string)$decimal;
        $sign = 1;
        if (strpos($str, '-') === 0) {
            $sign = -1;
            $str = substr($str, 1);
        }

        // 2. 如果没有小数点，直接返回整数 / 1
        if (strpos($str, '.') === false) {
            $num = (int)$str * $sign;
            return ['numerator' => $num, 'denominator' => 1];
        }

        // 3. 分离整数部分和小数部分
        list($intPart, $decPart) = explode('.', $str, 2);
        $intPart = (int)$intPart;           // 整数部分
        $decPart = rtrim($decPart, '0');    // 去掉小数部分末尾的“0”，避免 2.500 -> 25/10 可以先简化成 25/10

        // 如果小数部分全是 0，比如 “3.0”，rtrim 后可能变成空串，则可当作整数
        if ($decPart === '') {
            $num = ($intPart) * $sign;
            return ['numerator' => $num, 'denominator' => 1];
        }

        // 4. 小数部分长度
        $decLen = strlen($decPart);

        // 5. 初步构造“去小数”后的分子和分母
        //    例如 “2.75” -> intPart=2, decPart="75", decLen=2
        //    去小数分子 = 2 * 100 + 75 = 275，分母 = 100
        $denominator = intval(str_repeat('1', $decLen)); // 例如 decLen=2 -> "11" 不行，正确的是 10^2 = 100
        // 但上面方式有误：str_repeat('1',2) = "11"。应该用 pow(10, decLen)。
        $denominator = (int)pow(10, $decLen);

        $integerTimesDen = $intPart * $denominator;
        $decimalAsInt  = (int)$decPart;    // 小数部分当作整数
        $numerator = $integerTimesDen + $decimalAsInt;

        // 6. 求最大公约数
        $gcd = function (int $a, int $b): int {
            // 经典欧几里得算法
            $a = abs($a);
            $b = abs($b);
            if ($b === 0) {
                return $a;
            }
            while ($b !== 0) {
                $t = $b;
                $b = $a % $b;
                $a = $t;
            }
            return $a;
        };

        $commonDivisor = $gcd($numerator, $denominator);
        // 7. 约分
        $numeratorReduced   = (int)(($numerator / $commonDivisor) * $sign);
        $denominatorReduced = (int)($denominator / $commonDivisor);

        return [
            $numeratorReduced,
            $denominatorReduced
        ];
    }

    function gmpMulAndHex(string $decStr): string
    {

        list($numerator, $denominator) = $this->decimalToFraction($this->grade);
        var_dump($numerator);
        // 把乘 1.5 转成 “乘 3 再除 2”
        $g = gmp_init($decStr, 10);
        $g3 = gmp_mul($g, $numerator);
        $res = gmp_div_q($g3, (string)$denominator);       // 商，向下取整
        return '0x' . strtoupper(gmp_strval($res, 16));
    }

    public function transact ($tx) {

        if (!isset($this->credential)) {
            throw new Exception('credential not set');
        }

        $from = $this->credential->getAddress();

        $tx['from'] = $from;

        if (!isset($tx['nonce'])) {
            $tx['nonce'] = $this->getTransactionCount($from);
        }
        if (!isset($tx['chainId'])) {
            $chainId = $tx['chainId'] = $this->netVersion();
        }
        if (!isset($tx['value'])) {
            if (isset($this->value)) {
                $tx['value'] = $this->value;
            } else {
                $tx['value'] = '0x0';
            }
        }

        if (!isset($tx['gasPrice'])) {
            if (isset($this->gasPrice)) {
                $tx['gasPrice'] = $this->gasPrice;
            } else {
                $tx['gasPrice'] = $this->estimateGasPrice();
            }
        }

        if (!isset($tx['gasLimit'])) {
            if (isset($this->gasLimit)) {
                $tx['gasLimit'] = $this->gasLimit;
            } else {
                $tx['chainId'] = Utils::toHex($tx['chainId'], 1);
                $tx['gasLimit'] = $this->estimateGasUsage($tx);
            }
        }

        if(isset($this->grade) && $this->grade != 1){
            $price = $this->gmpMulAndHex(hexToDecimal($tx['gasPrice']));
            $tx['gasPrice'] = $price;
            $gasLimit = $this->gmpMulAndHex(hexToDecimal($tx['gasLimit']));
            $tx['gasLimit'] = $gasLimit;
        }

        $tx['chainId'] = $chainId;
        //  var_dump(hexToDecimal($tx));

        $stx = $this->credential->signTransaction($tx);
        $cb = new Callback;
        $this->web3->eth->sendRawTransaction($stx, $cb);
        $tx['grade'] = $this->grade;
        return ['tx_id'=>$cb->result,'param'=>$tx];
//        return $cb->result;
    }

}