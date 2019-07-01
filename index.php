<?php

/************************************************
*************************************************
Require handler file
*************************************************
************************************************/
require_once './src/Handler.php';
$handler = new \Handler\Handler(include('config/mail.php'));

$response = $handler->get_all();

?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="robots" content="noindex, nofollow">

        <meta http-equiv="Pragma"  content="no-cache">
        <meta http-equiv="Expires" content="-1">

        <meta name="author" content="Francesco Passanante">
        <meta name="description" content="A little IMAP reader for collect your e-mail messages in your web application">

        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    </head>
    <body>
        <div class="alert alert-<?php echo $response['status'] ? 'success' : 'danger';?>" role="alert">
          <?php echo $response['message'];?>
        </div>
        <?php if(is_array($response['emails']) && count($response['emails']) > 0) { ?>
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Subject</th>
                    <th scope="col">From - Address</th>
                    <th scope="col">Size</th>
                    <th scope="col">Date</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($response['emails'] as $index => $key) { ?>
                <tr>
                    <th scope="row"><?php echo $key['number'];?></th>
                    <td><?php echo $key['subject'];?></td>
                    <td><?php echo $key['from'].' - '.$key['address'];?></td>
                    <td><?php echo $key['size'];?></td>
                    <td><?php echo $key['date'];?></td>
                    <td><i class="far fa-trash-alt"></i></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php } ?>
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
        <script src="https://kit.fontawesome.com/b959326008.js"></script>
    </body>
</html>