# bsctool
## Binance
[link](http://sc.hubwiz.com/codebag/bsctool-php/)

Quickly increase Binance Smart Chain BNB/BEP20 digital asset support capabilities for PHP applications, and support the use of your own nodes or third-party service APIs.

1. Overview of the development kit
-------

The BscTool development kit is suitable for quickly adding support for Binance Smart Chain BNB/BEP20 digital assets for PHP applications, not only supporting application scenarios using its own BSC blockchain nodes, but also supporting BSC blockchain official node API services. Lightweight deployment scenarios.

The BscTool development kit mainly includes the following features:

* Support BSC blockchain native BNB transfer transaction and balance inquiry
* Support the deployment and interaction of smart contracts on the BSC chain, support BEP20 token transfer transactions and account arrival tracking
* Support offline signature of transactions on the BSC chain to avoid revealing private keys
* Support the use of own nodes or third-party nodes, such as public nodes officially provided by BSC

The BscTool package runs in the **Php 7.1+** environment, the current version is 1.0.0, and the main classes/interfaces and relationships are shown in the following figure:

![bsctool uml](img/bsctool-uml.png)

The main code file list of BscTool is as follows:

code file

illustrate

bsc.php/src/Kit.php

BscTool development kit entry class

bsc.php/src/Bep20.php

BEP20 smart contract packaging class

bsc.php/src/SmartContract.php

BSC smart contract package class

bsc.php/src/Credential.php

Identity class on the BSC blockchain for transaction signature

bsc.php/src/NodeClient.php

BSC node protocol encapsulation class

bsc.php/src/Callback.php

Php callback helper class

bsc.php/src/Helper.php

Miscellaneous Helper Function Set

contracts/WizToken.sol

Example BEP20 Token Contract

bin/build-contracts.sh

Contract Compilation Script Tool

demo/credential-demo.php

Demonstrate how to create a new BSC account or import an existing private key

demo/bnb-demo.php

Demonstration of BNB transfer and balance inquiry

demo/bep20-demo.php

Demonstrate BEP20 token transfer and balance inquiry

demo/bep20-event-demo.php

Demonstration of BEP20 token arrival monitoring

demo/deploy-contract-demo.php

Demo code, deployment of smart contracts

vendor

Third-party dependency package directory

composer.json

composer configuration file

2. Use sample code
--------

Before using the sample code, please set the following configuration information in `demo/config.php` according to the actual situation:

* NETWORK: the blockchain network to be connected to, optional: mainNet - BSC main chain, testNet - BSC test chain
* ALICE\_ADDR: The main test account used by the demo program. This account will be used to deploy token contracts, perform BNB and BEP20 token transfer transactions, etc. Therefore, a certain amount of BNB balance is required.
* ALICE\_SK: The private key corresponding to the main test account

### 2.1 Address creation and recovery on BSC chain

`demo/credential-demo.php` demonstrates how to use BscTool to create a new BSC on-chain address, or import an existing private key to rebuild an account.

Enter the demo code directory in the terminal and execute the following command:

    ~$ cd ~/bsctool/demo
    ~/bsctool/demo$ php credential-demo.php
    

The execution result is as follows:

![](img/credential-demo.png)

### 2.2 BNB transfer and balance inquiry

`demo/bnb-demo.php` demonstrates how to use BscTool to implement BNB transfer and balance inquiry.

Enter the demo code directory in the terminal and execute the following command:

    ~$ cd ~/bsctool/demo
    ~/bsctool/demo$ php bnb-demo.php
    

The execution result is as follows:

![](img/bnb-demo.png)

### 2.3 BSC smart contract deployment

`demo/deploy-contract-demo.php` demonstrates how to deploy EVM smart contracts using BscTool.

Enter the demo code directory in the terminal and execute the following command:

    ~$ cd ~/bsctool/demo
    ~/bsctool/demo$ php deploy-contract-demo.php
    

The execution result is as follows:

![](img/deploy-contract-demo.png)

### 2.4 BEP20 token transfer and balance inquiry

`demo/bep20-demo.php` demonstrates how to use BscTool to implement operations such as BEP20 token transfer and balance inquiry.

Enter the demo code directory in the terminal and execute the following command:

    ~$ cd ~/bsctool/demo
    ~/bsctool/demo$ php bep20-demo.php
    

The execution result is as follows:

![](img/bep20-demo.png)

### 2.5 BEP20 Token Arrival Tracking

`demo/bep20-event-demo.php` demonstrates how to use the contract event query function of BscTool to realize the arrival tracking of BEP20 tokens.

Enter the demo code directory in the terminal and execute the following command:

    ~$ cd ~/bsctool/demo
    ~/bsctool/demo$ php bep20-event-demo.php
    

The execution result is as follows:

![](img/bep20-event-demo.png)

3. Use BscTool
-------------

The Kit class is the entry point of the BscTool development kit. Using this class, you can quickly implement the following functions:

* BNB transfer and balance inquiry
* BEP20 token transfer, authorization, balance inquiry, etc.

### 3.1 Instantiation of Kit

Kit instantiation needs to pass in the `NodeClient` object and the `Credential` object. These two parameters respectively encapsulate the API provided by the BSC node and the user identity information for transaction signature.

For example, the following code creates a Kit instance that connects to the BSC main chain and uses the specified private key to sign the transaction:

    //use bsctool\Kit;
    //use bsctool\NodeClient;
    //use bsctool\Credential;
    
    $kit = new Kit(
      NodeClient::mainNet(), //Access the main chain
      Credential::fromKey('0x87c12d....d435') //Use the specified private key
    );
    

### 3.2 BNB transfer and balance inquiry

Use the `transfer()` method of the Kit object to transfer BNB, for example to send 0.1 BNB:

    //use bsctool\Kit;
    
    $to = '0x90F8bf6...0e7944Ea8c9C1'; //transfer destination address
    $amount = bn('100000000000000000'); //transfer amount, in the smallest unit
    $txid = $kit->transfer($to,$amount); //Submit BNB transfer transaction
    echo 'txid => ' . $txid . PHP_EOL; //Display transaction ID
    

Note: The amount needs to be converted to the smallest unit, since BNB has 18 decimal places, so 0.1 BNB = 100000000000000000 smallest unit.

Use the `balanceOf()` method to query the BNB balance of the specified address, for example:

    $addr = '0x90F8bf6...0e7944Ea8c9C1'; //The address on the BSC chain to be queried
    $balance = $kit->balanceOf($addr); //Query BNB balance, according to the smallest unit
    echo 'balance => ' . $balance . PHP_EOL; //Display BNB balance
    

### 3.3 BEP20 Token Transfer

Use the `bep20()` method of the Kit object to obtain the specified BEP20 token contract instance, and then call the `transfer()` method of the contract to transfer BEP20 tokens. For example, the following code transfers 123.4567 BUSD-T between specified addresses (token contract address: 0x55d398326f99059ff775485246999027b3197955):

    //use bsctool\Kit;
    
    $to = 'TDN3QY85Jft3RwgyatjRNmrwRmwkn8qwqx'; //transfer destination address
    $amount = bn('123456700000000000000'); //Transfer the amount of BEP20 tokens
    $contractAddr = '0x55d398326f99059ff775485246999027b3197955' //The deployment address of the BUSD-T token contract
    $txid = $kit->bep20($contractAddr)
                ->transfer($to,$amount); //transfer BEP20 tokens
    echo 'txid => ' . $txid . PHP_EOL; //Display transfer transaction ID
    

### 3.4 BEP20 Token Balance Query

Use the `bep20()` method to obtain the specified BEP20 token contract instance, and then call the `balanceOf()` method of the contract to query the token balance. For example, the following code queries the BUSD-T token balance of the specified address:

    //use bsctool\Kit;
    
    $contractAddr = '0x55d398326f99059ff775485246999027b3197955' //The deployment address of the BUSD-T token contract
    $balance = $kit->bep20($contractAddr)
                   ->balanceOf('0x90F8bf6...0e7944Ea8c9C1'); //Query the token balance of address 0x90F8...
    echo 'balance => ' . $balance . PHP_EOL; //Display token balance
    

### 3.5 BEP20 Token Arrival Tracking

Use the `bep20()` method to obtain the specified BEP20 token contract instance, and then call the `getTransferEvents()` method of the contract instance to query the transfer events of the specified conditions.

The `getTransferEvents()` method can be used to track the arrival status of the specified address. For example, query address 0x90F8... BUSD-T token arrival event in the latest block:

    //use bsctool\Kit;
    
    $contractAddr = '0x55d398326f99059ff775485246999027b3197955' //The deployment address of the BUSD-T token contract
    $events = $kit->bep20($contractAddr)
                  ->getTransferEvents( //Query arrival events
                    [], // Transfer out account, an empty array means no specific transfer account is required
                    ['0x90F8bf6...0e7944Ea8c9C1'], //Receive account, only query the account arrival event of address 0x90F8...
                    'latest', //Query the starting block number, latest means to use the latest block
                    'latest' //Query the end block number, latest means to use the latest block
                  );
    
    foreach($events as $event){
      echo 'block => ' . $event->blockNumber . PHP_EOL; //The event block number
      echo 'from => ' . $event->params['from'] . PHP_EOL; //transfer out account
      echo 'to => ' . $event->params['to'] . PHP_EOL; //transfer to account
      echo 'value => ' . $event->params['value'] . PHP_EOL; //transfer amount
    }
    

The result returned by the `getTransferEvents()` method is an array of event objects, and the main fields of each member object are described as follows:

* blockHash: The block hash triggered by the event
* blockNumber: The block number triggered by the event
* transactionHash: The transaction ID that triggered the event
* address: the contract address triggered by the event
* name: event name, for example, the name of the transfer event is: Transfer
* params: Array of event parameters, for example, the transfer event contains the following three parameters:
    * from: transfer account
    * to: transfer to account
    * value: transfer amount

4. BSC blockchain identity and address representation
---------------

In BscTool, the `Credential` object is used to represent a user identity in the BSC blockchain, and an ordinary string is used to represent an address in the BSC blockchain. The difference between the two is that Credential contains the user's private key information. Can be used to sign transactions and therefore requires protection.

Create a new account using the static method `create()` of the Credential class. For example, the following code creates a new account and displays its private key, public key, and address:

    //use bsctool\Credential;
    
    $credential = Credential::create(); //Create a new account
    echo 'private key => ' . $credential->getPrivateKey() . PHP_EOL; //Display private key
    echo 'public key => ' . $credential->getPublicKey() . PHP_EOL; //Display public key
    echo 'address => ' . $credential->getAddress() . PHP_EOL; //Display address
    

Credential can be instantiated by importing an existing private key using the static method `fromKey()`. For example, the following code imports an existing private key and displays the address:

    //use bsctool\Credential;
    
    $credential = Credential::fromKey('0x7889...023a'); //Import existing private key
    echo 'address => ' . $credential->getAddress() . PHP_EOL; //Display the corresponding address
    

5. Using NodeClient
--------------

The NodeClient class encapsulates the RPC access protocol for BSC nodes. When instantiating NodeClient, you need to specify the node URL to connect to, for example, using a local full node:

    //use bsc\NodeClient;
    
    $client = new NodeClient('http://localhost:8545');
    

When using the official BSC node, the NodeClient class also provides two static functions `mainNet()` and `testNet()`, which are used to access the officially provided main chain node and test chain node respectively.

For example, the following code is equivalent:

    //use bsctool\NodeClient;
    
    $client = new NodeClient('https://bsc-dataseed.binance.org/');
    $tc = NodeClient::mainNet(); //equivalent to above
    
    $tc = new NodeClient('https://data-seed-prebsc-1-s1.binance.org:8545/');
    $tc = NodeClient::testNet(); //equivalent to above
