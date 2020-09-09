<?php
session_start();

if(!isset($_SESSION["login"]))
{
    header("Location: index.php");
    exit();
}

if(isset($_GET["id"]))
{
    $id = $_GET["id"];

    if(!preg_match("/^[a-z0-9]+$/i", $id))
    {
        header("Location: index.php");
        exit();
    }

    $productArray = array();
    $productLines = file("products.txt");

    foreach($productLines as $productLine)
    {
        $productInfo = preg_split("/\|/", $productLine);

        if($productInfo[0] === $id)
        {
            $alreadyDownvoted = false;
            $downvoteList = preg_split("/,/", $productInfo[8]);

            foreach($downvoteList as $downvoted)
            {
                if($downvoted === $_SESSION["name"])
                {
                    $alreadyDownvoted = true;
                }
            }

            if(!$alreadyDownvoted)
            {
                if($productInfo[8] == "")
                {
                    $productArray[] = $productInfo[0]."|".$productInfo[1]."|".$productInfo[2]."|".$productInfo[3]."|".$productInfo[4]."|".$productInfo[5]."|".$productInfo[6]."|".$productInfo[7]."|".$_SESSION["name"]."|".PHP_EOL;
                }
                else
                {
                    $productArray[] = $productInfo[0]."|".$productInfo[1]."|".$productInfo[2]."|".$productInfo[3]."|".$productInfo[4]."|".$productInfo[5]."|".$productInfo[6]."|".$productInfo[7]."|".$productInfo[8].",".$_SESSION["name"]."|".PHP_EOL;
                }
            }
            else
            {
                $productArray[] = $productLine;
            }
        }
        else
        {
            $productArray[] = $productLine;
        }
    }

    $productsFile = fopen("products.txt", "w");

    foreach($productArray as $product)
    {
        fwrite($productsFile, $product);
    }

    fclose($productsFile);

    header("Location: index.php");
    exit();
}
else
{
    header("Location: index.php");
    exit();
}
