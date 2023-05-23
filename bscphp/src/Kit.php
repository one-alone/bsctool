<?php
namespace bsctool;

use Exception;

class Kit{
    protected $client;
    protected $credential;
    protected $transactor;

    function __construct($client, $credential){
        $this->client = $client;
        $this->credential = $credential;

        $this->transactor = new Transactor($client, $credential);
    }

    function getSender(){
        return $this->credential->getAddress();
    }

    function balanceOf($addr){
        return $this->client->getBalance($addr);
    }

    function transfer($to, $value){
        $tx = [
            'to' => $to,
            'value' => $value
        ];
        return $this->transactor->transact($tx);
    }

    //abi, bytecode, args...
    function deployContract(){
        $args = func_get_args();
        if(count($args) < 2) {
            throw new Exception('no enough deploy parameters');
        }
        $abi = array_shift($args);
        $bytecode = array_shift($args);
        $contract = new SmartContract($this->client, $this->credential, $abi);
        $contract->bytecode($bytecode);
        return $contract->instantiate(...$args);
    }

    function waitForConfirmation($txid, $timeout = 300){
        return $this->client->waitForConfirmation($txid, $timeout);
    }

    function bep20($addr,$abi=null){
        return new Bep20($this->client, $this->credential, $addr,$abi);
    }
    function bep721($addr,$abi=null){
        return new Bep721($this->client, $this->credential, $addr,$abi);
    }


}