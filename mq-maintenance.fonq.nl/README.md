# RabbitMQ maintenance tool
A webinterface for RabbitMQ that provides some functionality that is not available in their own installation.  Most prominently the possibility to "hot" change queue configurations. 

### Installation

* Prerequisites
  * Php 7.1 or higher with ext-curl and ext-json enabled.
  * Apache or Ngnix with mod_rewrite or its counterpart enabled.
  * Composer https://getcomposer.org/  
  * A rabbit MQ installation with the management plugin + webinterface enabled.
    
* Install
  * Copy config.example.php to config.php.
  * Fill in some RabbitMQ instance configuration in config.php.
  * Configure your webserver so the document root points to ./public     
  * Run `composer install` and `composer dump-autoload` to install dependencies.
     
* Run
  * Just open in your browser.
  * You should see a basic authentication window, use your RabbitMQ username + password to sign in.
  
#### Usage

##### Backup
It is always a good idea to start by making a backup. Instructions can be found on the bottom of this page. 

##### Requeue dead lettered
After signing in click on queues and under the column name look for **failed.dead**. You should the contents of the dead letter queue with a button to requeue a single message and a general requeue button. It is possible to modify messages befor you requeue.

##### View routing key topology map
The routing key topology map shows what queue's listen to which routing keys. This way it is easy for you to see who is receving what.  

##### Generate (lots of) test messages.
Test messages can be send to the topic exchange, this is the way messages usually arrive in RabbitMQ at Fonq. When you do this the message is routed / copied to all the queue's that listen to the routing key that you provice (see the section topology map). You can also send messages to a queue using the direct exchange. Sending messages this way ensures that they are delivered only to the queue that you intended the message to arrive on. 

###### Topic version
If you want to send messages to the exchange using the topic method, choose "amq.topic" in the exchange field and fill in the routing key that you chose. A handy setting is the "message count" which allows you so send many (duplicate) messages at once.

###### Direct version
If you want to send messages to the direct exchange e.g. delivery them to a single queue, choose amq.direct or leave the exchange field empty (direct = default). In the routing key field enter the name of the queue, enter some payload and select the amount of test messages that you want to send. And ofcourse press publish to actually send them.

##### Modify queues while running
Be very carefull here, first do a dry run on the test environment and check of everything went as expected. It is very easy to make a painfull mistake. When you made sure you know what you are doing, sign in and click on "Queues" -> "Queue settings". You should be able to change any RabbitMQ setting. 

Under the hood this first creates a temporary exchange and a temporary queue, adds all the routing keys that the original queue had to the temp exchange / queue combo, removes the routing keys from the original queue, requeue's all the messages over the temp exchange, deletes the original queue, re-creates the original queue with new properties, then does the above steps in reverse to get the messages back and lastly removes the temp queue and exchange.
  
##### Bulk move messages
In the Queuelist you can see a button "Move messages". Here you can move messages so another queue while conserving the original routing key. The logic works mostly the same as the steps that need to be done to change a queue's properties.

#### Backup
It is always a good idea to start by making a backup. You can download the configuration by visiting this url: http://localhost:15672/api/definitions. Ofcourse replace localhost with the url or ip of your RabbitMQ instance. 

It might however be a better id to use curl. Replace guest:guest with your username and password.

`` curl -i -u guest:guest http://localhost:15672/api/definitions > backup.txt ``

When you want to restore your configuration, you can post the text file back to the API.
 
``  curl -i -u guest:guest -H "content-type:application/json" -XPOST -d'<<insert-contents-of-backup.txt-here>>' http://localhost:15672/api/definitions `` 
