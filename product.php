<?php
define("FILE_SIZE_LIMIT", 4000000);

if(count($_GET) > 0)
{
    $id = $_GET["id"];
    $title = $_GET["title"];
    $picture = $_GET["picture"];
    $description = $_GET["description"];
    $email = $_GET["email"];
    $name = $_GET["name"];
    $price = $_GET["price"];

    $validProduct = true;
    $productExists = false;

    if(!preg_match("/^[a-z0-9]+$/i", $id))
    {
        $validProduct = false;
    }
    else if(!preg_match("/^[a-z0-9 ,.'-]+( [a-z0-9 ,.'-]+)*$/i", $title))
    {
        $validProduct = false;
    }
    else if(!preg_match("/^[a-z0-9]+\.(jpg|jpeg|png|gif)$/i", $picture))
    {
        $validProduct = false;
    }
    else if(!preg_match("/^[a-z0-9 ,.'-]+( [a-z0-9 ,.'-]+)*$/i", $description))
    {
        $validProduct = false;
    }
    else if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $email))
    {
        $validInfo = false;
    }
    else if(!preg_match("/^[a-z '-]+ [a-z '-]+$/i", $name))
    {
        $validInfo = false;
    }
    else if(!preg_match("/^([1-9][0-9]*|0)*(\.[0-9]{1,2})?$/", $price))
    {
        $validProduct = false;
    }

    $productLine = file("products.txt");

    foreach($productLine as $product)
    {
        $productInfo = preg_split("/\|/", $product);
        $productString = $id."|".$title."|".$picture."|".$description."|".$email."|".$name."|".$price."|".$productInfo[7]."|".$productInfo[8]."|".PHP_EOL;

        if($productString === $product)
        {
            $productExists = true;
        }
    }

    if(!$productExists)
    {
        header("Location: index.php");
        exit();
    }

    if($validProduct)
    {
        if(!isset($_COOKIE["product1"]))
        {
            setcookie("product1", $id);
        }
        else if(!isset($_COOKIE["product2"]))
        {
            setcookie("product2", $id);
        }
        else if(!isset($_COOKIE["product3"]))
        {
            setcookie("product3", $id);
        }
        else if(!isset($_COOKIE["product4"]))
        {
            setcookie("product4", $id);
        }
        else
        {
            for($i = 1; $i < 4; $i++)
            {
                setcookie("product" . strval($i), $_COOKIE["product" . strval($i + 1)]);
            }

            setcookie("product4", $id);
        }
    }
    else
    {
        header("Location: index.php");
        exit();
    }
}
else
{
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>COMP 3015</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

<div id="wrapper">

    <div class="container">

        <div class="row">
            <div class="col-md-6 col-md-offset-3">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <h1 class="login-panel text-center text-muted">
                    COMP 3015 Final Project
                </h1>
                <hr/>
            </div>
        </div>

        <div class="row">
            <div class="col-md-offset-3 col-md-6">
                <div>
                    <p>
                        <a class="btn btn-default" href="index.php">
                            <i class="fa fa-arrow-left"></i>
                        </a>
                    </p>
                </div>
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <?php echo $title; ?>
                    </div>
                    <div class="panel-body text-center">
                        <p>
                            <?php echo '<img class="img-rounded img-thumbnail" src="products/' . $picture . '"/>'; ?>
                        </p>
                        <p class="text-muted text-justify">
                            <?php echo $description; ?>
                        </p>
                    </div>
                    <div class="panel-footer ">
                        <?php
                            echo '<span><a href="mailto:' . $email . '"><i class="fa fa-envelope"></i> ' . $name . '</a></span>';
                            echo '<span class="pull-right">$' . strval(number_format($price, 2)) . '</span>';
                        ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<div id="newPost" class="modal fade" tabindex="-1" role="dialog">
<div class="modal-dialog" role="document">
    <form role="form" method="post" action="">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">New Profile</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Username</label>
                    <input class="form-control disabled" disabled>
                </div>
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input class="form-control" type="file" name="picture">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <input type="submit" class="btn btn-primary" value="Submit!"/>
            </div>
        </div><!-- /.modal-content -->
    </form>
</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
</body>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</html>
