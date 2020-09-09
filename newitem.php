<?php
define("FILE_SIZE_LIMIT", 4000000);

session_start();

if(!isset($_SESSION["login"]))
{
    header("Location: index.php");
    exit();
}

if($_GET["type"] === "new-item")
{
    $title = trim($_POST["title"]);
    $price = trim($_POST["price"]);
    $description = trim($_POST["description"]);
    $picture = $_FILES["picture"];

    $validProduct = true;

    if(!preg_match("/^[a-z0-9 ,.'-]+( [a-z0-9 ,.'-]+)*$/i", $title))
    {
        $validProduct = false;
    }
    else if(!preg_match("/^([1-9][0-9]*|0)*(\.[0-9]{1,2})?$/", $price))
    {
        $validProduct = false;
    }
    else if(!preg_match("/^[a-z0-9 ,.'-]+( [a-z0-9 ,.'-]+)*$/i", $description))
    {
        $validProduct = false;
    }
    else if($picture["size"] > FILE_SIZE_LIMIT || !($picture["type"] == "image/jpeg" || $picture["type"] == "image/png" || $picture["type"] == "image/gif"))
    {
        $validProduct = false;
    }

    if($validProduct)
    {
        $productId = uniqid();
        $pictureExtension = "";

        if($picture["type"] === "image/jpeg")
        {
            $pictureExtension = ".jpg";
        }
        else if($picture["type"] === "image/png")
        {
            $pictureExtension = ".png";
        }
        else if($picture["type"] === "image/gif")
        {
            $pictureExtension = ".gif";
        }

        $pictureName = md5(microtime()) . $pictureExtension;
        move_uploaded_file($picture["tmp_name"], "products/" . $pictureName);

        $createTime = time();

        $productFile = fopen("products.txt", "a+");
        fwrite($productFile, $productId."|".$title."|".$pictureName."|".$description."|".$_SESSION["email"]."|".$_SESSION["name"]."|".$price."|".$createTime."||".PHP_EOL);
        fclose($productFile);
    }

    header("Location: index.php");
}
else
{
    header("Location: index.php");
}
