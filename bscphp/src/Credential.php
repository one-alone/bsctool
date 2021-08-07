<?php
namespace bsctool;

use Elliptic\EC;
use kornrunner\Keccak;
use Web3p\EthereumTx\Transaction;

class Credential{
  private $keyPair;
  
  public function __construct($keyPair){
    $this->keyPair = $keyPair;
  }
  
  public function getPublicKey() {
    return $this->keyPair->getPublic()->encode('hex');
  }
  
  public function getPrivateKey() {
    return $this->keyPair->getPrivate()->toString(16,2);
  }
  
  public function getAddress() {
    $pubkey = $this->getPublicKey();
    return "0x" . substr(Keccak::hash(substr(hex2bin($pubkey), 1), 256), 24);
  }
  
  public function signTransaction($raw){
		$txreq = new Transaction($raw);
    $privateKey = $this->getPrivateKey();
    $signed = '0x' . $txreq->sign($privateKey);
    return $signed;
  }
  
  public static function create(){
    $ec = new EC('secp256k1');
    $keyPair = $ec->genKeyPair();
    return new self($keyPair);
  }  
  
  public static function fromKey($privateKey){
    $ec = new EC('secp256k1');
    $keyPair = $ec->keyFromPrivate($privateKey);
    return new self($keyPair);
  }
  
}
