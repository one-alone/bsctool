<?php
/**
 * Created by PhpStorm.
 * User: haiqing.lin
 * Date: 28/07/21
 * Time: 16:13
 */


use bsctool\Callback;
use bsctool\Credential;
use bsctool\SmartContract;
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
    /**
     * @var string
     * https://bsc-mainnet.rpcfast.com?api_key=S3X5aFCCW9MobqVatVZX93fMtWCzff0MfRj9pvjGKSiX5Nas7hz33HwwlrT5tXRM
     * http://8.219.208.204:10001/xxxxabc/bsc
     *         /**
     * 推荐
     *
     * https://bsc-dataseed.binance.org/
     *
     * https://bsc-dataseed1.defibit.io/
     * https://bsc-dataseed1.ninicoin.io/
     * 备份
     *
     * https://bsc-dataseed2.defibit.io/
     * https://bsc-dataseed3.defibit.io/
     * https://bsc-dataseed4.defibit.io/
     * https://bsc-dataseed2.ninicoin.io/
     * https://bsc-dataseed3.ninicoin.io/
     * https://bsc-dataseed4.ninicoin.io/
     * https://bsc-dataseed1.binance.org/
     * https://bsc-dataseed2.binance.org/
     * https://bsc-dataseed3.binance.org/
     * https://bsc-dataseed4.binance.org/
     * BC RPC 端点：
     *
     * https://dataseed1.binance.org/
     * https://dataseed2.binance.org/
     * https://dataseed3.binance.org/
     * https://dataseed4.binance.org/
     * https://dataseed1.defibit.io/
     * https://dataseed2.defibit.io/
     * https://dataseed3.defibit.io/
     * https://dataseed4.defibit.io/
     * https://dataseed1.ninicoin.io/
     * https://dataseed2.ninicoin.io/
     * https://dataseed3.ninicoin.io/
     * https://dataseed4.ninicoin.io/
     */

    public static $_default_sk = '0xf2f42c0c55c65b4e374784c056e8df421ed933405e52fb97444bb06f';
    public static $_net = 'https://binance.llamarpc.com';

    public static $_nets = [
        'http://8.219.208.204:10001/xxxxabc/bsc',
        'https://bsc.publicnode.com',
        'https://bsc-mainnet.rpcfast.com?api_key=S3X5aFCCW9MobqVatVZX93fMtWCzff0MfRj9pvjGKSiX5Nas7hz33HwwlrT5tXRM',
        'https://endpoints.omniatech.io/v1/bsc/mainnet/public',
        'https://docs.nodereal.io/nodereal/meganode/introduction',
        'https://app.ankr.com/api',
        'https://chainstack.com/build-better-with-binance-smart-chain',
        'https://getblock.io/nodes/bsc',
        'https://quicknode.com',
        'https://docs.blockvision.org/blockvision/chain-apis/bnb-chain-api'
    ];

    public static function myNet()
    {
        $nowNet = self::$_net;
        return $nowNet;
        $redis = App::redis();
        $nowNet = $redis->get(BscNet::NOW_NET_REDIS_KEY);
        if (!$nowNet) {
            $nowNet = self::$_net;
//            $res = UserService::bscNet();
//            if($res['code'] == 0){
//                $nowNet = $res['data'];
//                $redis->set(BscNet::NOW_NET_REDIS_KEY,$nowNet,10 * 60);
//            }else{
//                $nowNet = self::$_net;
//            }
        }
//        if(App::getConfig('bsc_net') != $nowNet){
//            App::setConfig('bsc_net',$nowNet);
//        }
        return $nowNet;
    }

    /**
     * @param $str
     * @param bool $prefix
     * @return string
     */
    public static function hex($str, $prefix = true)
    {
        $bn = gmp_init($str);
        $ret = gmp_strval($bn, 16);
        return $prefix ? '0x' . $ret : $ret;
    }

    public static function bn($n)
    {
        return new phpseclib\Math\BigInteger($n);
    }

    public static function hex2str($bn)
    {
        return gmp_strval(Utils::toBn($bn)->value);
    }

    /**
     * 查询交易状态
     * @author haiqing.lin
     * @date   2021/7/28 0028
     */
    public static function transferState($txid, $timeOut = 60, $verify_net = '')
    {
        try {
            if ($verify_net) {
                $net = $verify_net;
            } else {
                $net = self::myNet();
            }
            $c = NodeClient::create($net);
            $re = $c->waitForConfirmation($txid, $timeOut);
            if (!$re) {
                return $re;
            }
            return Common::object2array($re);
        } catch (Throwable $e) {
            if ($e->getMessage() == 'tx not confirmed yet.') {
                return false;
            }
        }
    }

    /**
     * 创建地址
     * @author haiqing.lin
     * @date   2021/7/28 0028
     */
    public static function create()
    {
        $c = Credential::create();

        $data['address'] = $c->getAddress();
        $data['private_key'] = $c->getPrivateKey();
        $data['public_key'] = $c->getPublicKey();

        return $data;
    }

    /**
     * 转出
     * @param $alice_sk  转出人私钥
     * @param $toAddress 转出地址
     * @param $num       转出数量
     * @param $tokenAddr token地址
     * @return string
     * @throws Exception
     * @author haiqing.lin
     * @date   2021/7/28 0028
     */
    public static function transferParam($alice_sk, $toAddress, $num, $tokenAddr = '',$param = [], $net = '')
    {
        if (!$alice_sk) {
            return BaseService::res('密钥缺失',1);
        }
        if (!$net) {
            $net = self::myNet();
        }
        try {
            $kit = new Kit(
                NodeClient::create($net),
                Credential::fromKey($alice_sk)
            );
            if ($tokenAddr) {
                $kit = $kit->bep20($tokenAddr);
            }

            $res = $kit->transferParam(
                $toAddress,
                self::hex($num),
                $param
            );
            return BaseService::res($res);
        } catch (Throwable $e) {
            return BaseService::res($e->getMessage(), 1);
//            if ($e->getMessage() == 'invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go struct field TransactionArgs.chainId of type *hexutil.Big') {
//                return self::transfer($alice_sk, $recipient, $num, $tokenAddr);
//            }
//            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 转出
     * @param $alice_sk  转出人私钥
     * @param $toAddress 转出地址
     * @param $num       转出数量
     * @param $tokenAddr token地址
     * @return string
     * @throws Exception
     * @author haiqing.lin
     * @date   2021/7/28 0028
     */
    public static function transfer($alice_sk, $toAddress, $num, $tokenAddr = '', $net = '')
    {
        if (!$alice_sk) {
            return BaseService::res('密钥缺失',1);
        }
        if (!$net) {
            $net = self::myNet();
        }
        try {
            $kit = new Kit(
                NodeClient::create($net),
                Credential::fromKey($alice_sk)
            );
            if ($tokenAddr) {
                $kit = $kit->bep20($tokenAddr);
            }

            $txid = $kit->transfer(
                $toAddress,
                self::hex($num)
            );
            return BaseService::res($txid);
        } catch (Throwable $e) {
            return BaseService::res($e->getMessage(), 1);
//            if ($e->getMessage() == 'invalid argument 0: json: cannot unmarshal hex string without 0x prefix into Go struct field TransactionArgs.chainId of type *hexutil.Big') {
//                return self::transfer($alice_sk, $recipient, $num, $tokenAddr);
//            }
//            return ['code' => 1, 'msg' => $e->getMessage()];
        }
    }


    /**
     * 获取币种信息
     * @param $token
     * @return array
     */
    public static function tokenInfo( $token = '0x0000000000000000000000000000000000000000')
    {

        try {
            $kit = new Kit(
                NodeClient::create(self::myNet()),
                Credential::fromKey(self::$_default_sk)
            );
            $inst = $kit->bep20($token);

            $tokenInfo = [
                'name' => $inst->name(),
                'symbol' => $inst->symbol(),
                'decimals' => gmp_strval(Utils::toBn($inst->decimals())->value),
                'totalSupply' => gmp_strval(Utils::toBn($inst->totalSupply())->value),
            ];
            return BaseService::res($tokenInfo);
        } catch (Throwable $e) {
            return BaseService::res($e->getMessage(), 1);
        }
        // try {
//            $decimals = $inst->decimals();
//             //   var_dump($inst->name());
//      //  var_dump($inst->symbol());
//       // var_dump($inst->decimals());
//        $nonce = gmp_strval(Utils::toBn($inst->decimals())->value);
//        var_dump($nonce);
//        var_dump(gmp_strval(Utils::toBn($inst->totalSupply())->value));
//      //  $balance = $inst->balanceOf($erc20->getAccount("ETHEREUM-ADDRESS"));
//       // var_dump($balance);
//      //  var_dump($inst->getScaledValue($balance));

//        } catch (Throwable $e) {
//            sleep(1);
//
//        }

    }

    /**
     * 根据区块号获取交易
     * @param $blockNumber
     * @return mixed
     */
    public static function getBlockByNumber($blockNumber)
    {
        $tc = NodeClient::create(self::myNet());
        return $tc->getBlockByNumber($blockNumber);
    }

    /**
     * 调用ETH方法 blockNumber 查询最新区块高度
     * @param $method
     * @return string
     */
    public static function callEthMethod($method = 'blockNumber', $verify_net = '')
    {
        if ($verify_net) {
            $net = $verify_net;
        } else {
            $net = self::myNet();
        }
        $tc = NodeClient::create($net);
        $res = $tc->callEthMethod($method);
        if ($method != 'blockNumber') {
            return $res;
        }
        return gmp_strval(Utils::toBn($res)->value);

    }

    /**
     * 获取余额
     * @param        $alice_sk
     * @param string $tokenAddr
     * @return float|int
     * @author haiqing.lin
     * @date   2021/7/30 0030
     */
    public static function balance($tokenAddr = '', $addr = '', $decimal = 0,$alice_sk = '')
    {
        if (!$alice_sk) {
            $alice_sk =  self::$_default_sk;
        }
        try {
            $kit = new Kit(
                NodeClient::create(self::myNet()),
                Credential::fromKey($alice_sk)
            );
            if ($addr) {
                $sender = $addr;
            } else {
                $sender = $kit->getSender();
            }
            if ($tokenAddr) {
                $kit = $kit->bep20($tokenAddr);
                if (!$decimal) {
                    $decimal = gmp_strval(Utils::toBn($kit->decimals())->value);
                }
            }
            $balance = (string)$kit->balanceOf($sender);

            return Common::myFloat(bcdiv($balance, pow(10,$decimal), $decimal));
            // return round($balance / $decimal, 8);

        } catch (Throwable $e) {
            return ['err'=>$e->getMessage()];
            sleep(1);
            return self::balance($alice_sk, $tokenAddr, $addr, $decimal);
        }

    }

    /**
     * 查询地址的交易记录
     * @param $address
     * @return array
     * @author haiqing.lin
     * @date   2021/8/1
     */
    public static function getTxlist($address, $tokenAddr = '')
    {
        if ($tokenAddr) {
            $address = $tokenAddr;
        }
//        $url = 'https://api.bscscan.com/api?module=account&action=txlist&address='.$address.
//            '&startblock=0&endblock=99999999&page=1&offset=10000&sort=desc&apikey=YourApiKeyToken&token=';
        $url = 'https://api.polygonscan.com/api?module=account&action=txlist&address=' . $address .
            '&startblock=0&endblock=99999999&page=1&offset=10000&sort=desc&apikey=YourApiKeyToken&token=';
        $data = file_get_contents($url);
        $data = json_decode($data, true);
        if (strpos($data['message'], 'OK') !== false) {
            return array_column($data['result'], null, 'hash');
        }
        return [];
    }


    /**
     * NFT项目扫块
     * @param string $type
     * @param string $begin
     * @param string $end
     * @return array|string
     */
    public static function nftQueryGas($type = 'tool', $begin = 'latest', $end = 'latest', $net = '')
    {
        $contract = AppConfig::getConfig('contract', $type);
        $private_key = $contract['admin_private_key'];
        if (!$net) {
            $net = self::myNet();
        }
        try {
            $kit = new Kit(
                NodeClient::create($net),
                Credential::fromKey($private_key)
            );
            $contractAddress = $contract['token'];
            $kotAbi = $contract['abi'];
            $inst = $kit->bep20($contractAddress, $kotAbi);
            $re = $inst->queryEvents([], $begin, $end);
            return $re;
        } catch (Throwable $e) {
            if ($e->getMessage() == 'too many requests') {
                return self::nftQueryGas($type, $begin, $end);
            }
            return 'error:' . $e->getMessage();
        }

    }

    public static function nonce($username, $verify_net = '')
    {
        if ($verify_net) {
            $net = $verify_net;
        } else {
            $net = self::myNet();
        }
        $web3 = NodeClient::create($net);
        $cb = new Callback;
        $web3->eth->getTransactionCount($username, 'pending', $cb);
        return gmp_strval(Utils::toBn($cb->result)->value);
    }


    public static function gasPrice($verify_net = '')
    {
        if ($verify_net) {
            $net = $verify_net;
        } else {
            $net = self::myNet();
        }
        $web3 = NodeClient::create($net);
        $cb = new Callback;
        $web3->eth->gasPrice($cb);
        $gas = gmp_strval(Utils::toBn($cb->result)->value);
        return $gas;
    }


    /**
     * nft项目通用调用
     * @param $name
     * @param ...$arg
     * @return array
     */
    public static function nft($type, $name, ...$arg)
    {
        $verify_net = '';

        if (is_array($type)) {
            $verify_net = $type['verify_net'] ?? '';

            if (isset($type['private_key'])) {
                $private_key = $type['private_key'];
            }
            if (isset($type['value'])) {
                $value = $type['value'];
            }
            $type = $type['type'];
        }
        if ($verify_net) {
            $net = $verify_net;
        } else {
            $net = self::myNet();
        }
        $contract = AppConfig::getConfig('contract', $type);
        if (!isset($private_key)) {
            $private_key = $contract['admin_private_key'];
        }

        try {
            $web3 = NodeClient::create($net);

            $kit = new Kit(
                $web3,
                Credential::fromKey($private_key)
            );

            $contractAddress = $contract['token'];
            $abi = $contract['abi'];
            $inst = $kit->bep20($contractAddress, $abi);
            $cb = new Callback;
            $web3->eth->getTransactionCount($contract['admin_address'], 'pending', $cb);
            $nonce = gmp_strval(Utils::toBn($cb->result)->value);
            if ($verify_net) {
                $inst->setGasLimit(200000);
                $web3->eth->gasPrice($cb);
                $gas = gmp_strval(Utils::toBn($cb->result)->value);
                $gas = bcmul($gas, '1.5');
                //  $inst->setGasPrice(intval($gas));
                if (isset($value)) {
                    $inst->setValue($value);
                }

            }

            $re = $inst->$name(...$arg);
            return ['res' => $re, 'nonce' => $nonce ?? -1];
        } catch (Throwable $e) {
            Helper::log($e, 'bsc_nft');
            $return = [
                'err' => $e->getMessage(),
                'msg' => $e
            ];
            return $return;
        }

    }

    //兑换签名顺序
//用户，tokenA,A量，当前合约地址，tokenB, B量，过期时间，当前nonce
    public static function swapSign($username, $tokenIn, $numIn, $tokenOut, $numOut, $outTime)
    {
        $type = 'swap_tool';
        $res = self::getNonce($type, $username);
        if (is_array($res)) {
            return ['nonce' => -1];
        }
        $contract = AppConfig::getConfig('contract', $type);
        $nonce = gmp_strval(Utils::toBn($res)->value);
        $sign = [
            strtolower($username),
            Utils::stripZero(strtolower($tokenIn)),
            //$numIn,
            gmp_strval(Utils::toBn($numIn)->value),
            Utils::stripZero(strtolower($contract['token'])),
            Utils::stripZero(strtolower($tokenOut)),
            //    $numOut,
            gmp_strval(Utils::toBn($numOut)->value),
            $outTime,
            gmp_strval(Utils::toBn($res)->value)
        ];
        //$admin_private_key = SignKey::getPrivateKey($type);
        return self::getSign($sign, $contract['admin_private_key'], $nonce);


    }

    /**
     *      * 合约扣币签名
     * 签名顺序说明
     * //--
     * 1/当前合约地址
     * 2/发送者地址
     * 3/token地址
     * 4/token数量
     * 5/otype
     * 6/oid
     * 7/过期时间
     * 8/nonce
     * 方法 deposit(address _token, uint256 _amount, uint256 otype, uint256 oid, uint256 _deadline, uint8 v, bytes32 r, bytes32 s)
     * @param string $username 当前发交易地址
     * @param string $token token地址
     * @param $amount 转账的数量
     * @param int $otype 转账类型
     * @param int $oid 转账oid
     * @param int $outTime 过期时间
     * @return array
     */
    public static function signDeduction(string $username, string $token, $amount, int $otype, int $oid, int $outTime): array
    {
        $type = 'deduction_tool';
        $res = self::getNonce($type, $username);
        if (is_array($res)) {
            return ['nonce' => -1];
        }
        $contract = AppConfig::getConfig('contract', $type);
        $nonce = gmp_strval(Utils::toBn($res)->value);
        $sign = [
            strtolower($contract['token']),
            Utils::stripZero(strtolower($username)),
            Utils::stripZero(strtolower($token)),
            gmp_strval(Utils::toBn($amount)->value),
            $otype,
            $oid,
            $outTime,
            $nonce
        ];
        return self::getSign($sign, $contract['admin_private_key'], $nonce);
    }


    /**
     * 合约提现转账签名
     * 签名参数顺序
     *_sender,//当前发交易地址
     *_tokenAddress,//token地址
     *address(this),//当前工具合约地址
     *_amount,//量
     *_deadline,//过期时间
     *nonces[_sender]//发交易地址的在当前合约的nonce
     *
     * 方法 withdraw(address tokenAddress, uint256 tokenAmount, uint256 _deadline, uint256 oid, uint8 v, bytes32 r, bytes32 s)
     * @param string $type 类型
     * @param string $username 当前发交易地址
     * @param string $token token地址
     * @param $amount //转账的数量
     * @param string $contract 当前工具合约地址
     * @param int $outTime 过期时间 当前时间戳加过期的时间秒
     * @param string $privateKey 管理员私钥
     * @return array
     */
    public static function signWithdraw(string $type, string $username, string $token, $amount, string $contract, int $outTime, string $privateKey): array
    {
        $res = self::getNonce($type, $username);

        $nonce = gmp_strval(Utils::toBn($res)->value);
        $sign = [
            strtolower($username),
            strtolower($token),
            Utils::stripZero(strtolower($contract)),
            gmp_strval(Utils::toBn($amount)->value),
            $outTime,
            $nonce
        ];
        return self::getSign($sign, $privateKey, $nonce);
    }

    public static function getNonce($type, $username)
    {
        return self::nft($type, 'nonces', $username);
    }

    /**
     * 公共签名
     * @param array $arr 需签名的数组
     * @param string $privateKey 管理员私钥
     * @return array
     */
    public static function getSign(array $arr, string $privateKey, $nonce): array
    {
        $paramHash = '';
        foreach ($arr as $value) {
            if (!Utils::isAddress((string)$value)) {
                $value = Utils::toHex($value);
                $len = strlen($value);
                if ($len < 64) {
                    for ($i = 0; $i < 64 - $len; $i++) {
                        $value = '0' . $value;
                    }
                }
            }
            $paramHash .= $value;
        }
        $paramHash = Utils::sha3($paramHash);
        $pre = "\x19Ethereum Signed Message:\n32";
        $pre = Utils::toHex($pre, true);
        $value = $pre . Utils::stripZero($paramHash);
        $singStr = Utils::sha3($value);//keccak256
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate($privateKey);
        $signre = $key->sign($singStr, ['canonical' => true]);
        $signature['r'] = '0x' . $signre->r->toString(16);
        $signature['s'] = '0x' . $signre->s->toString(16);
        $signature['v'] = $signre->recoveryParam + 27;
        $signature['nonce'] = $nonce;

        return $signature;
    }

    /**
     * _sender,//当前发交易地址
     * _tokenAddress,//token地址
     * address(this),//当前工具合约地址
     * _amount,//量
     * _deadline,//过期时间
     * nonces[_sender]//发交易地址的在当前合约的nonce
     */
    public static function signLPWithdraw1($type, $username, $contract, $number, $ionz, $outTime)
    {
        $re = self::nft($type, 'nonces', $username);

        $sign = [
            strtolower($username),
            Utils::stripZero(strtolower($contract)),
            strtolower($contract),
            gmp_strval(Utils::toBn($number)->value),

            gmp_strval(Utils::toBn($ionz)->value),
            $outTime,
            gmp_strval(Utils::toBn($re)->value)
        ];
        //var_dump($sign);
        return self::getSignStr($sign);
    }
    //withdraw(uint256 amountA, uint256 amountB, uint256 _deadline, uint256 oid, uint8 v, bytes32 r, bytes32 s)
//签名顺序说明
//bytes32 message = keccak256(abi.encodePacked("\x19Ethereum Signed Message:\n32", keccak256(abi.encodePacked(_sender, amountA, address(this), amountB, _deadline, nonces[_sender]++))));
//
//_sender, amountA, address(this), amountB, _deadline, nonces[_sender]++))));
//用户地址，LP数量，当前工具地址，收益金额，过期时间，nonces查询值
    public static function signLPWithdraw($type, $username, $number, $contract, $ionz, $outTime)
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

    public static function getSignStr($arr)
    {
        $paramHash = '';
        foreach ($arr as $value) {
            if (!Utils::isAddress((string)$value)) {
                $value = Utils::toHex($value);
                $len = strlen($value);
                if ($len < 64) {
                    for ($i = 0; $i < 64 - $len; $i++) {
                        $value = '0' . $value;
                    }
                }
            }
            $paramHash .= $value;
        }
        $paramHash = Utils::sha3($paramHash);
        $pre = "\x19Ethereum Signed Message:\n32";//"\x19Ethereum Signed Message:\n32"
        $pre = Utils::toHex($pre, true);
        $value = $pre . Utils::stripZero($paramHash);
        $singStr = Utils::sha3($value);//keccak256
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate(UserDepositLp::$private_key);
        $signre = $key->sign($singStr, ['canonical' => true]);
        $signature['r'] = '0x' . $signre->r->toString(16);
        $signature['s'] = '0x' . $signre->s->toString(16);
        $signature['v'] = $signre->recoveryParam + 27;
        return $signature;
    }


    public static function sign2($contract, $username, $nft_id, $cr, $outTime)
    {
        $re = self::nft('nft', 'nonces',
            $contract,
            $username);
        $sign = [
            'username' => strtolower($username),
            'address' => Utils::stripZero(strtolower($contract)),
            'nft_id' => $nft_id,
            'cr' => $cr,
            'outTime' => $outTime,
            'nonces' => gmp_strval(Utils::toBn($re)->value)
        ];

        $singStr = self::getSignStr2($sign);
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPrivate(NftPledge1::$private_key);
        $signre = $key->sign($singStr, ['canonical' => true]);
        $signature['r'] = '0x' . $signre->r->toString(16);
        $signature['s'] = '0x' . $signre->s->toString(16);
        $signature['v'] = $signre->recoveryParam + 27;
        return $signature;
    }

    public static function getSignStr2($arr)
    {
        $paramHash = '';
        foreach ($arr as $k => $value) {
            if (!Utils::isAddress((string)$value)) {
                $value = Utils::toHex($value);
                $len = strlen($value);
                if ($len < 64) {
                    for ($i = 0; $i < 64 - $len; $i++) {
                        $value = '0' . $value;
                    }
                }
            }
            $paramHash .= $value;
        }
        $paramHash = Utils::sha3($paramHash);
        $pre = "\x19Ethereum Signed Message:\n32";
        $pre = Utils::toHex($pre, true);
        $value = $pre . Utils::stripZero($paramHash);
        return Utils::sha3($value);//keccak256
    }

}
