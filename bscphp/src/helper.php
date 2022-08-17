<?php
//gmp extension required

function hex($str, $prefix=true){
  $bn = gmp_init($str);
  $ret = gmp_strval($bn, 16);
  return $prefix ? '0x' . $ret : $ret;
}

function bn($n){
  return new phpseclib\Math\BigInteger($n);
}