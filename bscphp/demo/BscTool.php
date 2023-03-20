<?php
/**
 * Created by PhpStorm.
 * User: haiqing.lin
 * Date: 28/07/21
 * Time: 16:13
 */


use bsctool\Credential;
use bsctool\Kit;
use bsctool\NodeClient;
use Web3\Utils;
use Elliptic\EC;
/**
 * Class BscTool
 * bsc工具类
 */
class BscTool
{
    public static $_net = 'mainNet';

    /**
     * @param $str
     * @param bool $prefix
     * @return string
     */
    function hex($str, $prefix=true){
        $bn = gmp_init($str);
        $ret = gmp_strval($bn, 16);
        return $prefix ? '0x' . $ret : $ret;
    }

    function bn($n){
        return new phpseclib\Math\BigInteger($n);
    }

    public static function hex2str($bn){
        return gmp_strval(Utils::toBn($bn)->value);
    }
    /**
     * 查询交易状态
     * @author haiqing.lin
     * @date   2021/7/28 0028
     */
    public static  function transferState($txid,$timeOut = 60)
    {
        try {
            $c = NodeClient::create(self::$_net);
            $re = $c->waitForConfirmation($txid,$timeOut);
            if(!$re){
                return  $re;
            }
            return Common::object2array($re) ;
        }catch (Exception $e){
            if($e->getMessage() == 'tx not confirmed yet.'){
                return  false;
            }
        }
    }

    /**
     * 创建地址
     * @author haiqing.lin
     * @date   2021/7/28 0028
     */
    public static  function create()
    {
        $c = Credential::create();

        $data['address']     = $c->getAddress();
        $data['private_key'] = $c->getPrivateKey();
        $data['public_key']  = $c->getPublicKey();

        return $data;
    }

    /**
     * 转出
     * @param $alice_sk  转出人私钥
     * @param $recipient 转出地址
     * @param $num       转出数量
     * @param $tokenAddr token地址
     * @return string
     * @throws Exception
     * @author haiqing.lin
     * @date   2021/7/28 0028
     */
    public function transfer($alice_sk, $recipient, $num, $tokenAddr = '')
    {
        if(!$alice_sk){
            return ['code' => 1,'msg'=>'密钥缺失'];
        }
        try {
            $kit = new Kit(
                NodeClient::mainNet(),
                Credential::fromKey($alice_sk)
            );
            if ($tokenAddr) {
                $kit = $kit->bep20($tokenAddr);
            }

            $txid = $kit->transfer(
                $recipient,
                $this->hex($num)
            );
            return ['code' => 0,'msg'=>$txid];
        } catch (Exception $e) {

            if($e->getMessage() == 'invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go struct field TransactionArgs.chainId of type *hexutil.Big'){
                return  $this->transfer($alice_sk, $recipient, $num, $tokenAddr);
            }
            return ['code' => 1,'msg'=>$e->getMessage()];
        }
    }

    /**
     * 根据区块号获取交易
     * @return void
     */
    public function getBlockByNumber($blockNumber)
    {
        $tc   =  NodeClient::create(self::$_net);
        return $tc->getBlockByNumber($blockNumber);
    }

    /**
     * 调用ETH方法 blockNumber 查询最新区块高度
     * @return void
     */
    public function callEthMethod($method='blockNumber')
    {
        $tc   =  NodeClient::create(self::$_net);
        return $tc->callEthMethod($method);
    }

    /**
     * 获取余额
     * @param        $alice_sk
     * @param string $tokenAddr
     * @return float|int
     * @author haiqing.lin
     * @date   2021/7/30 0030
     */
    public function balance($alice_sk, $tokenAddr = '',$addr = '',$decimal=0)
    {
        if(!$alice_sk){
            return 0;
        }
        try {
            $kit = new Kit(
                NodeClient::create(self::$_net),
                Credential::fromKey($alice_sk)
            );
            if($addr){
                $sender =   $addr;
            }else{
                $sender  = $kit->getSender();
            }
            if ($tokenAddr) {
                $kit = $kit->bep20($tokenAddr);
            }
            $balance = (string) $kit->balanceOf($sender);
            if($decimal == 0){
                if($tokenAddr == '0xe41ccf516ecb31c4d106d0cebe12bb751ce8c57a') {
                    $decimal = 10000000000;
                } else {
                    $decimal = 1000000000000000000;
                }
            }
            return round($balance/$decimal,8);

        } catch (Exception $e) {
            sleep(1);
            return $this->balance($alice_sk, $tokenAddr);
        }

    }

    /**
     * 查询地址的交易记录
     * @param $address
     * @return array
     * @author haiqing.lin
     * @date   2021/8/1
     */
    public function getTxlist($address, $tokenAddr = '') {
        if($tokenAddr) {
            $address = $tokenAddr;
        }
        $url = 'https://api.bscscan.com/api?module=account&action=txlist&address='.$address.
            '&startblock=0&endblock=99999999&page=1&offset=10000&sort=desc&apikey=YourApiKeyToken&token=';
        $data = file_get_contents($url);
        $data = json_decode($data, true);
        if(strpos($data['message'],'OK') !== false) {
            return array_column($data['result'],null,'hash');
        }
        return [];
    }




    /**
     * 项目扫块
     * @param string $type
     * @param string $begin
     * @param string $end
     * @return array|string
     */
    public static function nftQueryGas($type='nft',$begin='latest',$end='latest')
    {
        $private_key = Task::$private_key;
        try {
            $kit = new Kit(
                NodeClient::create(self::$_net),
                Credential::fromKey($private_key)
            );
            $contractAddress = Nft::$addressArr[$type];
            $kotAbi = Nft::$abiArr[$type];
            $inst = $kit->bep20($contractAddress,$kotAbi);
            $re =  $inst->queryEvents([],$begin,$end);
            return $re;
        } catch (Exception $e) {
            if( $e->getMessage() == 'too many requests'){
                return  self::nftQueryGas($type,$begin,$end);
            }
            return 'error:'. $e->getMessage();
        }

    }


    /**
     * nft项目通用调用
     * @param $name
     * @param ...$arg
     * @return string
     */
    public static function nft($type,$name,...$arg)
    {

        if($type == 'deposit'){
            $private_key = UserDeposit::$private_key;
        }elseif($type == 'deposit_lp_solarix' || $type == 'deposit_lp_ionz'){
            $private_key = UserDepositLp::$private_key;
        } else{
            $private_key = Task::$private_key;
        }
        try {
            $kit = new Kit(
                NodeClient::create(self::$_net),
                Credential::fromKey($private_key)
            );
            // var_dump($kit);
            //  $sender  = $kit->getSender();
            $address = Nft::$addressArr;
            $contractAddress = $address[$type];
            $abiArr = Nft::$abiArr;
            $inst = $kit->bep20($contractAddress,$abiArr[$type]);
            $re =  $inst->$name(...$arg);
            return $re;

        } catch (Exception $e) {
            if($e->getMessage() == 'invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go struct field TransactionArgs.chainId of type *hexutil.Big'){
                return  self::nft($type,$name,...$arg);
            }
            $return = [
                'err'=>$e->getMessage(),
                'msg'=>$e
            ];
            return $return;
        }

    }

    //withdraw(uint256 amountA, uint256 amountB, uint256 _deadline, uint256 oid, uint8 v, bytes32 r, bytes32 s)
//签名顺序说明
//bytes32 message = keccak256(abi.encodePacked("\x19Ethereum Signed Message:\n32", keccak256(abi.encodePacked(_sender, amountA, address(this), amountB, _deadline, nonces[_sender]++))));
//
//_sender, amountA, address(this), amountB, _deadline, nonces[_sender]++))));
//用户地址，LP数量，当前工具地址，收益金额，过期时间，nonces查询值
    public static function signLPWithdraw($type,$username,$number,$contract,$ionz,$outTime)
    {
        $re = self::nft($type, 'nonces', $username);

        $sign = [
            strtolower($username),
            gmp_strval(Utils::toBn($number)->value),
            Utils::stripZero(strtolower($contract)),
            gmp_strval(Utils::toBn($ionz)->value),
            $outTime,
            gmp_strval(Utils::toBn($re)->value)
        ];
        //var_dump($sign);
        return self::getSignStr($sign);
    }
    public static function getSignStr($arr){
        $paramHash = '';
        foreach ($arr as $value){
            if(!Utils::isAddress((string)$value)){
                $value = Utils::toHex($value);
                $len = strlen($value);
                if($len < 64){
                    for ($i = 0;$i < 64 - $len; $i++){
                        $value = '0'. $value;
                    }
                }
            }
            $paramHash .=  $value;
        }
        $paramHash = Utils::sha3($paramHash);
        $pre = "\x19Ethereum Signed Message:\n32";//"\x19Ethereum Signed Message:\n32"
        $pre = Utils::toHex($pre,true);
        $value = $pre . Utils::stripZero($paramHash);
        $singStr = Utils::sha3($value);//keccak256
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate(UserDepositLp::$private_key);
        $signre = $key->sign($singStr,['canonical' => true]);
        $signature['r'] = '0x' . $signre->r->toString(16);
        $signature['s'] = '0x' . $signre->s->toString(16);
        $signature['v'] = $signre->recoveryParam + 27;
        return $signature;
    }

}
