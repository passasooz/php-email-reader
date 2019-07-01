# php-email-reader

A little IMAP reader for collect your e-mail messages in your web application

## Getting Started

Configure the array into config/mail.php with your data

### Installing

Just paste this on your command line

```
composer require passasooz/php-email-reader
```

Connect to IMAP
```
$handler->connect();
```

Get all e-mail
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

