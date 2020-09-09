<?php
define("EXPIRY_TIME", 3600);

session_start();

$id = $_GET["id"];

if(!isset($_SESSION["login"]) || !preg_match("/^[a-z0-9]+$/i", $id))
{
    header("Location: index.php");
    exit();
}

$username = $_SESSION["name"];

$keepProducts = array();
$products = file("products.txt");

foreach($products as $product)
{
    $productInfo = preg_split('/\|/', $product);

    if(!($id === $productInfo[0] && $username === $productInfo[5]))
    {
        $keepProducts[] = $product;
    }
    else
    {
        unlink("products/" . $productInfo[2]);

        for($i = 1; $i <= 4; $i++)
        {
            if(isset($_COOKIE["product" . strval($i)]))
            {
                if($_COOKIE["product" . strval($i)] == $id)
                {
                    setcookie("product" . strval($i), $id, time() - EXPIRY_TIME);
                }
            }
        }
    }
}

$productsFile = fopen("products.txt", "w");

foreach($keepProducts as $productLine)
{
    fwrite($productsFile, $productLine);
}

fclose($productsFile);

header("Location: index.php");
