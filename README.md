# php-email-reader

A little IMAP reader for collect your unseen e-mail messages in your web application

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

Example of usage in your php file
```
require_once 'path/to/Handler.php';
$handler = new \Handler\Handler();
```

Connect to IMAP
```
$handler->connect();
```

Disconnect from IMAP (require a $connection variable returned by connection to imap)
```
$handler->disconnect($connection);
```

Get all e-mail
```
$handler->all();
```

Get unseen e-mail
```
$handler->unseen();
```

Get seen e-mail
```
$handler->seen();
```

Get deleted e-mail
```
$handler->deleted();
```

To customize type of e-mail what you want list:

* **read criteria** - https://www.php.net/manual/en/function.imap-search.php 
* **extends Handler**
* **create function in your new Class** - for example
```
class Customize extends Handler {
	public function answered() {
		return $this->getEmails('ANSWERED');
	}
}
``` 
* **instance new class into a variable (i.e. $customize)**
* **just call**
```
$customize->answered();
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

