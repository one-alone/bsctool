<?php

namespace bsctool;

use Exception;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Web3;

class NodeClient extends Web3
{
    function __construct($url)
    {
        $provider = new HttpProvider(
            new HttpRequestManager($url, 300) //timeout
        );
        parent::__construct($provider);
    }

    static function create($network)
    {
        if ($network === 'mainNet'){
            return self::mainNet();
        }
        else if ($network === 'testNet'){
            return self::testNet();
        }
        else{
            return self::ownNet($network);
        }
    }

    static function ownNet($network)
    {
        return new self($network);
    }

    static function testNet($url = 'https://data-seed-prebsc-1-s1.binance.org:8545/')
    {
        return new self($url);
    }

    static function mainNet($url = 'https://bsc-dataseed1.ninicoin.io/' )
    {
        /**
         * 推荐

        https://bsc-dataseed.binance.org/
        https://bsc-dataseed1.defibit.io/
        https://bsc-dataseed1.ninicoin.io/
        备份

        https://bsc-dataseed2.defibit.io/
        https://bsc-dataseed3.defibit.io/
        https://bsc-dataseed4.defibit.io/
        https://bsc-dataseed2.ninicoin.io/
        https://bsc-dataseed3.ninicoin.io/
        https://bsc-dataseed4.ninicoin.io/
        https://bsc-dataseed1.binance.org/
        https://bsc-dataseed2.binance.org/
        https://bsc-dataseed3.binance.org/
        https://bsc-dataseed4.binance.org/
         * BC RPC 端点：

        https://dataseed1.binance.org/
        https://dataseed2.binance.org/
        https://dataseed3.binance.org/
        https://dataseed4.binance.org/
        https://dataseed1.defibit.io/
        https://dataseed2.defibit.io/
        https://dataseed3.defibit.io/
        https://dataseed4.defibit.io/
        https://dataseed1.ninicoin.io/
        https://dataseed2.ninicoin.io/
        https://dataseed3.ninicoin.io/
        https://dataseed4.ninicoin.io/
         */
        return new self($url);
    }

    function getBalance($addr)
    {
        $cb = new Callback;
        $this->getEth()->getBalance($addr, $cb);

        return $cb->result;
    }

    function broadcast($rawtx)
    {
        $cb = new Callback;
        $this->getEth()->sendRawTransaction($rawtx, $cb);

        return $cb->result;
    }

    function getReceipt($txid)
    {
        $cb = new Callback;
        $this->getEth()->getTransactionReceipt($txid, $cb);

        return $cb->result;
    }

    function waitForConfirmation($txid, $timeout = 300)
    {
        $expire = time() + $timeout;
        while (time() < $expire) {
            try {
                $receipt = $this->getReceipt($txid);
                if (!is_null($receipt)) return $receipt;
                sleep(2);
            } catch (Exception $e) {

            }
        }
        return false;
    }

    function getBlockByNumber()
    {
        $cb     = new Callback;
        $number = hex(436);
        $this->getEth()->getBlockByNumber($number, true, $cb);

        return $cb->result;
    }

    /**
     * 调用ETH方法 无参数查询方法
     * @return void
     */
    public function callEthMethod($method)
    {
        $cb     = new Callback;
        $this->getEth()->$method($cb);

        return $cb->result;
    }

}
