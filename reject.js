#!/usr/bin/env node

var amqp = require('amqplib');

amqp.connect('amqp://localhost').then(function(conn) {
  process.once('SIGINT', function() { conn.close(); });
  return conn.createChannel().then(function(ch) {
    
    var ok = ch.assertQueue('hello', {durable: true});
    
    return ok.then(function(_qok) {

      return ch.consume('hello', function(msg) {
        ch.nack(msg, false, false);
       // messages.push(msg.content.toString());
      }, {noAck: false});

      
    });
    
    // return ok.then(function(_consumeOk) {
    //   console.log(' [*] Waiting for messages. To exit press CTRL+C');
    // });
  });
}).then(null, console.warn);