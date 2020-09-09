<?php
define("SALT", "random_salt_1689");
define("EXPIRY_TIME", 3600);
define("MAX_DOWNVOTES", 5);

if(count($_POST) > 0)
{
    if($_GET["type"] === "signup")
    {
        $firstName = trim($_POST["first-name"]);
        $lastName = trim($_POST["last-name"]);
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $verifyPassword = $_POST["verify-password"];

        $validInfo = true;

        if(!preg_match("/^[a-z '-]+$/i", $firstName))
        {
            $validInfo = false;
        }
        else if(!preg_match("/^[a-z '-]+$/i", $lastName))
        {
            $validInfo = false;
        }
        else if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $email))
        {
            $validInfo = false;
        }
        else if(!preg_match("/((?=.*[a-z])(?=.*[0-9])(?=.*[!?|@])){8}/", $password))
        {
            $validInfo = false;
        }
        else if($verifyPassword !== $password)
        {
            $validInfo = false;
        }

        if($validInfo)
        {
            $hash = md5($password . SALT);

            $usersFile = fopen("users.txt", "a+");
            fwrite($usersFile, $firstName . "|" . $lastName . "|" . $email . "|" . $hash . "||" . PHP_EOL);
            fclose($usersFile);

            session_start();
            $_SESSION["login"] = true;
            $_SESSION["name"] = $firstName . " " . $lastName;
            $_SESSION["email"] = $email;

            header("Location: index.php");
            exit();
        }
    }
    else if($_GET["type"] === "login")
    {
        $email = trim($_POST["email"]);
        $password = $_POST["password"];

        $validInfo = true;

        if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $email))
        {
            $validInfo = false;
        }
        else if(!preg_match("/((?=.*[a-z])(?=.*[0-9])(?=.*[!?|@])){8}/", $password))
        {
            $validInfo = false;
        }

        if($validInfo && file_exists("users.txt"))
        {
            $hash = md5($password . SALT);
            $users = file("users.txt");

            foreach($users as $user)
            {
                $userInfo = preg_split('/\|/', $user);

                if($email === $userInfo[2] && $hash === $userInfo[3])
                {
                    session_start();
                    $_SESSION["login"] = true;
                    $_SESSION["name"] = $userInfo[0] . " " . $userInfo[1];
                    $_SESSION["email"] = $email;

                    header("Location: index.php");
                    exit();
                }
            }
        }
    }
    else
    {
        header("Location: index.php");
        exit();
    }
}
else if(isset($_GET["search"]))
{    
    session_start();

    $searchTerm = $_GET["search"];

    if($searchTerm == NULL || $searchTerm === "")
    {
        $searchFile = fopen("search.txt", "w");
        fclose($searchFile);

        header("Location: index.php");
        exit();
    }
    else
    {
        if(!preg_match("/^[a-z0-9 ,.'-]+( [a-z0-9 ,.'-]+)*$/i", $searchTerm))
        {
            header('Location: index.php');
            exit();
        }

        $searchArray = array();
        $products = file("products.txt");
    
        foreach($products as $product)
        {
            $productInfo = preg_split('/\|/', $product);

            if(preg_match("/^([a-z0-9 ,.'-]+)*([a-z0-9 ,.'-]+ )*".$searchTerm."( [a-z0-9 ,.'-]+)*([a-z0-9 ,.'-]+)*$/i", $productInfo[1]))
            {
                $searchArray[] = $product;
            }
        }

        $searchFile = fopen("search.txt", "w");
    
        foreach($searchArray as $searchItem)
        {
            fwrite($searchFile, $searchItem);
        }

        fclose($searchFile);
    }
}
else
{
    session_start();
}

$products = array();
$pinnedArray = array();

if(isset($_GET["search"]) && file_exists("search.txt") && $_GET["search"] !== "")
{
    $searchTerm = $_GET["search"];

    if(!preg_match("/^[a-z0-9 ,.'-]+( [a-z0-9 ,.'-]+)*$/i", $searchTerm))
    {
        $fileName = "products.txt";
    }
    else
    {
        $fileName = "search.txt";
    }
}
else
{
    $fileName = "products.txt";
}

if(file_exists($fileName) && file_exists("users.txt"))
{
    $pinnedProducts = array();
    $unpinnedProducts = array();
    $keepProduct = array();

    $userLines = file("users.txt");

    if(isset($_SESSION["name"]))
    {
        foreach($userLines as $userLine)
        {
            $userLineInfo = preg_split("/\|/", $userLine);

            if(($userLineInfo[0] . " " . $userLineInfo[1]) === $_SESSION["name"])
            {
                $pinnedArray = preg_split("/,/", $userLineInfo[4]);
            }
        }
    }

    $productLines = file($fileName);

    foreach($productLines as $productLine)
    {
        $productInfo = preg_split("/\|/", $productLine);
        $downvotedList = preg_split("/,/", $productInfo[8]);

        if((time() - (int)$productInfo[7] < EXPIRY_TIME) && (count($downvotedList) <= MAX_DOWNVOTES))
        {
            $productItem["name"] = $productInfo[5];
            $productItem["price"] = $productInfo[6];
            $productItem["id"] = $productInfo[0];
            $productItem["title"] = $productInfo[1];
            $productItem["picture"] = $productInfo[2];
            $productItem["description"] = $productInfo[3];
            $productItem["email"] = $productInfo[4];
            $productItem["downvoted"] = $productInfo[8];

            $keepProduct[] = $productLine;

            if(in_array($productItem["id"], $pinnedArray))
            {
                $pinnedProducts[] = $productItem;
            }
            else
            {
                $unpinnedProducts[] = $productItem;
            }
        }
        else
        {
            unlink("products/" . $productInfo[2]);

            for($i = 1; $i <= 4; $i++)
            {
                if(isset($_COOKIE["product" . strval($i)]))
                {
                    if($_COOKIE["product" . strval($i)] == $productInfo[0])
                    {
                        setcookie("product" . strval($i), $productInfo[0], time() - EXPIRY_TIME);
                    }
                }
            }
        }
    }

    $productsFile = fopen($fileName, "w");

    foreach($keepProduct as $productLine)
    {
        fwrite($productsFile, $productLine);
    }

    fclose($productsFile);

    $products = array_merge($pinnedProducts, $unpinnedProducts);
}

$viewedItems = array();
$sortedViewedItems = array();

if(file_exists("products.txt") && (isset($_COOKIE["product1"]) || isset($_COOKIE["product2"]) || isset($_COOKIE["product3"]) || isset($_COOKIE["product4"])))
{
    $viewedProducts = array();
    $viewedItem = array();

    for($i = 1; $i <= 4; $i++)
    {
        if(isset($_COOKIE["product" . strval($i)]))
        {
            $viewedProducts[] = $_COOKIE["product" . strval($i)];
        }
    }

    $viewedLines = file("products.txt");

    foreach($viewedProducts as $viewedProduct)
    {
        foreach($viewedLines as $viewedLine)
        {
            $viewedInfo = preg_split("/\|/", $viewedLine);
    
            if($viewedProduct === $viewedInfo[0] && (time() - $viewedInfo[7]) < EXPIRY_TIME)
            {
                $viewedItem["name"] = $viewedInfo[5];
                $viewedItem["price"] = $viewedInfo[6];
                $viewedItem["id"] = $viewedInfo[0];
                $viewedItem["title"] = $viewedInfo[1];
                $viewedItem["picture"] = $viewedInfo[2];
                $viewedItem["description"] = $viewedInfo[3];
                $viewedItem["email"] = $viewedInfo[4];
                
                $viewedItems[] = $viewedItem;
            }
        }
    }

    sort($viewedItems);

    $tempArray = array();
    $tempItemArray = array();
    $same = false;

    if(count($viewedItems) > 1)
    {
        for($i = 1; $i < count($viewedItems); $i++)
        {
            $sortedTempArray = array();

            if($viewedItems[$i - 1]["name"] == $viewedItems[$i]["name"])
            {
                $tempArray[] = $viewedItems[$i - 1];
                $same = true;

                if($i == count($viewedItems) - 1)
                {
                    $tempArray[] = $viewedItems[$i];

                    foreach($tempArray as $temp)
                    {
                        $tempItemArray["price"] = $temp["price"];
                        $tempItemArray["name"] = $temp["name"];
                        $tempItemArray["id"] = $temp["id"];
                        $tempItemArray["title"] = $temp["title"];
                        $tempItemArray["picture"] = $temp["picture"];
                        $tempItemArray["description"] = $temp["description"];
                        $tempItemArray["email"] = $temp["email"];
        
                        $sortedTempArray[] = $tempItemArray;
                    }

                    rsort($sortedTempArray);
                    $sortedViewedItems = array_merge($sortedViewedItems, $sortedTempArray);
                    $same = false;
                    $tempArray = array();
                }
            }
            else if($viewedItems[$i - 1]["name"] != $viewedItems[$i]["name"] && $same)
            {
                $tempArray[] = $viewedItems[$i - 1];

                foreach($tempArray as $temp)
                {
                    $tempItemArray["price"] = $temp["price"];
                    $tempItemArray["name"] = $temp["name"];
                    $tempItemArray["id"] = $temp["id"];
                    $tempItemArray["title"] = $temp["title"];
                    $tempItemArray["picture"] = $temp["picture"];
                    $tempItemArray["description"] = $temp["description"];
                    $tempItemArray["email"] = $temp["email"];
    
                    $sortedTempArray[] = $tempItemArray;
                }

                rsort($sortedTempArray);
    
                $sortedViewedItems = array_merge($sortedViewedItems, $sortedTempArray);
                $same = false;
                $tempArray = array();

                if($i == count($viewedItems) - 1)
                {
                    $sortedViewedItems[] = $viewedItems[$i];
                }
            }
            else
            {
                $sortedViewedItems[] = $viewedItems[$i - 1];

                if($i == count($viewedItems) - 1)
                {
                    $sortedViewedItems[] = $viewedItems[$i];
                }
            }
        }
    }
    else
    {
        $sortedViewedItems = $viewedItems;
    }
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
            <div class="col-md-6 col-md-offset-3">
                <?php
                if(isset($_SESSION["login"]))
                {
                    echo '<button class="btn btn-default" data-toggle="modal" data-target="#newItem"><i class="fa fa-photo"></i> New Item</button>';
                    echo '<a href="logout.php" class="btn btn-default pull-right"><i class="fa fa-sign-out"> </i> Logout</a>';
                }
                else
                {
                    echo '<a href="#" class="btn btn-default pull-right" data-toggle="modal" data-target="#login"><i class="fa fa-sign-in"> </i> Login</a>';
                    echo '<a href="#" class="btn btn-default pull-right" data-toggle="modal" data-target="#signup"><i class="fa fa-user"> </i> Sign Up</a>';
                }
                ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <h2 class="login-panel text-muted">
                    Recently Viewed
                </h2>
                <hr/>
            </div>
        </div>
        <div class="row">
            <?php
            foreach($sortedViewedItems as $item)
            {
                echo '<div class="col-md-3">
                        <div class="panel panel-info">
                            <div class="panel-heading">' . $item["title"] . '</div>
                            <div class="panel-body text-center">
                                <p>
                                    <a href="product.php?title='.$item["title"].'&description='.$item["description"].'&picture='.$item["picture"].'&email='.$item["email"].'&name='.$item["name"].'&price='.$item["price"].'&id='.$item["id"].'">
                                        <img class="img-rounded img-thumbnail" src="products/' . $item["picture"] . '"/>
                                    </a>
                                </p>
                                <p class="text-muted text-justify">' . $item["description"] . '</p>
                                <br />
                            </div>
                            <div class="panel-footer ">
                                <span><a href="mailto:' . $item["email"] . '" data-toggle="tooltip" title="Email seller"><i class="fa fa-envelope"></i> ' . $item["name"] . '</a></span>
                                <span class="pull-right">$' . strval(number_format($item["price"], 2)) . '</span>
                            </div>
                        </div>
                    </div>';
            }
            ?>
        </div>

        <div class="row">
            <div class="col-md-3">
                <h2 class="login-panel text-muted">
                    Items For Sale
                </h2>
                <hr/>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                    <form class="form-inline" method="get" action="index.php">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon"><i class="fa fa-search"></i></div>
                                <?php
                                if(isset($_GET["search"]) && $_GET["search"] != "")
                                {
                                    if(!preg_match("/^[a-z0-9 ,.'-]+( [a-z0-9 ,.'-]+)*$/i", $searchTerm))
                                    {
                                        echo '<input id="share" type="text" class="form-control" name="search" placeholder="Search"/>';
                                    }
                                    else
                                    {
                                        echo '<input id="share" type="text" class="form-control" name="search" value="' . $_GET["search"] . '"/>';
                                    }
                                }
                                else
                                {
                                    echo '<input id="share" type="text" class="form-control" name="search" placeholder="Search"/>';
                                }
                                ?>
                            </div>
                        </div>
                        <input type="submit" class="btn btn-default" value="Search"/>
                        <!-- The copyLink() function called on click is used to copy the URL to the clipboard -->
                        <button class="btn btn-default" type="button" data-toggle="tooltip" title="Shareable Link!" onclick="copyLink()"><i class="fa fa-share"></i></button>
                        <div id="copy-link" style="display:none"></div>
                    </form>
                <br/>
            </div>
        </div>

        <?php
        $productCounter = 1;

        foreach($products as $product)
        {
            if($productCounter % 4 == 0)
            {
                echo '<div class="row">';
            }

            echo '<div class="col-md-3">';
                    if(in_array($product["id"], $pinnedArray) && isset($_SESSION["login"]))
                    {
                        echo '<div class="panel panel-warning">';
                    }
                    else
                    {
                        echo '<div class="panel panel-info">';
                    }
                    
                    echo '<div class="panel-heading">';

                            if(isset($_SESSION["login"]))
                            {
                                echo '<a class="" href="pin.php?id=' . $product["id"] . '" data-toggle="tooltip" title="Unpin item">
                                        <i class="fa fa-dot-circle-o"></i>
                                    </a>';
                            }

                            echo '<span>' . $product["title"] . '</span>';

                            if(isset($_SESSION["login"]) && $product["name"] === $_SESSION["name"])
                            {
                                echo '<span class="pull-right">
                                        <a class="" href="delete.php?id=' . $product["id"] . '" data-toggle="tooltip" title="Delete item">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </span>';
                            }

                    echo '</div>
                        <div class="panel-body text-center">
                            <p>
                                <a href="product.php?title='.$product["title"].'&description='.$product["description"].'&picture='.$product["picture"].'&email='.$product["email"].'&name='.$product["name"].'&price='.$product["price"].'&id='.$product["id"].'">
                                    <img class="img-rounded img-thumbnail" src="products/' . $product["picture"] .'"/>
                                </a>
                            </p>
                            <p class="text-muted text-justify">' . $product["description"] . '</p>';

                            if(isset($_SESSION["login"]) && $product["name"] !== $_SESSION["name"])
                            {
                                $alreadyDownvoted = false;
                                $downvoteList = preg_split("/,/", $product["downvoted"]);

                                foreach($downvoteList as $downvote)
                                {
                                    if($downvote == $_SESSION["name"])
                                    {
                                        $alreadyDownvoted = true;
                                    }
                                }

                                if(!$alreadyDownvoted)
                                {
                                    echo '<a class="pull-left" href="downvote.php?id=' . $product["id"] . '" data-toggle="tooltip" title="Downvote item">
                                            <i class="fa fa-thumbs-down"></i>
                                        </a>';
                                }
                                else
                                {
                                    echo '<br />';
                                }
                            }
                            else
                            {
                                echo '<br />';
                            }

                    echo '</div>
                        <div class="panel-footer ">
                            <span><a href="mailto:' . $product["email"] . '" data-toggle="tooltip" title="Email seller"><i class="fa fa-envelope"></i> '. $product["name"] . '</a></span>
                            <span class="pull-right">$' . strval(number_format($product["price"], 2)) . '</span>
                        </div>
                    </div>
                </div>';

            if($productCounter % 4 == 0)
            {
                echo '<div>';
            }

            $productCounter++;
        }

        if($productCounter % 4 != 0)
        {
            echo '</div>';
        }
        ?>
    </div>
</div>

<div id="login" class="modal fade" tabindex="-1" role="dialog">
<div class="modal-dialog" role="document">
    <form role="form" method="post" action="index.php?type=login">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text-center">Login</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Email</label>
                    <input class="form-control" type="text" name="email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input class="form-control" type="password" name="password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <input type="submit" class="btn btn-primary" value="Login!"/>
            </div>
        </div><!-- /.modal-content -->
    </form>
</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div id="newItem" class="modal fade" tabindex="-1" role="dialog">
<div class="modal-dialog" role="document">
    <form role="form" method="post" action="newitem.php?type=new-item" enctype="multipart/form-data">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text-center">New Item</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Title</label>
                    <input class="form-control" type="text" name="title">
                </div>
                <div class="form-group">
                    <label>Price</label>
                    <input class="form-control" type="text" name="price">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input class="form-control" type="text" name="description">
                </div>
                <div class="form-group">
                    <label>Picture</label>
                    <input class="form-control" type="file" name="picture">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <input type="submit" class="btn btn-primary" value="Post Item!"/>
            </div>
        </div><!-- /.modal-content -->
    </form>
</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div id="signup" class="modal fade" tabindex="-1" role="dialog">
<div class="modal-dialog" role="document">
    <form role="form" method="post" action="index.php?type=signup">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title text-center">Sign Up</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>First Name</label>
                    <input class="form-control" type="text" name="first-name">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input class="form-control" type="text" name="last-name">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input class="form-control" type="text" name="email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input class="form-control" type="password" name="password">
                </div>
                <div class="form-group">
                    <label>Verify Password</label>
                    <input class="form-control" type="password" name="verify-password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <input type="submit" class="btn btn-primary" value="Sign Up!"/>
            </div>
        </div><!-- /.modal-content -->
    </form>
</div><!-- /.modal-dialog -->
</div><!-- /.modal -->


</body>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })

    // Function to copy the URL to clipboard
    function copyLink() {
        var url = "http://localhost/index.php?search=";
        var id = document.getElementById("share").value;
        var dummyElement = $("<input>").val(url + id).appendTo("body").select()
        document.execCommand("copy");
    }
</script>
</html>
