# php-email-reader

A little IMAP reader for collect your e-mail messages in your web application

## Getting Started

Configure the array into config/mail.php with your data

### Installing

Just paste this on your command line

```
composer require passasooz/php-email-reader
```

Configure config/mail.php with your imap data (for example)
```
return [

    'host' => '', //YOUR IMAP imap.gmail.com

    'port' => 993, //YOUR PORT 993

    'username' => '', //YOUR EMAIL francescopassanante@gmail.com

    'password' => '', //YOUR PASSWORD 123asd456qwe

    'protocol' => '' //YOUR PROTOCOL ssl

];
```

To assign Handler Class to a variable ($handler) check index.php of library!

Connect to IMAP
```
$handler->connect($host, $port, $protocol, $username, $password);
```

Disconnect from IMAP (require a $connection variable returned by connection to imap)
```
$handler->disconnect($connection);
```

Get all e-mail (return an array with status, message and emails)
```
$handler->get_all();
```

Enjoy it :)

## Built With

* [Composer](https://getcomposer.org/download/) - Dependency Management

## Authors

* **Francesco Passanante** - (https://github.com/passasooz)

## License

This project is licensed under the MIT License

## Acknowledgments

* Valerio Giacomelli for inspiration
* Me for patience ;)

