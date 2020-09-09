<?php
session_start();

if(!isset($_SESSION["login"]))
{
    header("Location: index.php");
    exit();
}

if(isset($_GET["id"]))
{
    $itemId = $_GET["id"];

    if(!preg_match("/^[a-z0-9]+$/i", $itemId))
    {
        header("Location: index.php");
        exit();
    }

    $keepUsers = array();
    $users = file("users.txt");

    foreach($users as $user)
    {
        $userInfo = preg_split("/\|/", $user);

        if(($userInfo[0] . " " . $userInfo[1]) === $_SESSION["name"])
        {
            $pinnedArray = array();
            $alreadyPinned = false;
            $pinnedItems = preg_split("/,/", $userInfo[4]);

            foreach($pinnedItems as $item)
            {
                if($item === $itemId)
                {
                    $alreadyPinned = true;
                }
                else
                {
                    $pinnedArray[] = $item;
                }
            }
               
            if($alreadyPinned)
            {
                if(count($pinnedArray) > 0)
                {
                    $newPinnedList = $pinnedArray[0];

                    if(count($pinnedArray) > 1)
                    {
                        for($i = 1; $i < count($pinnedArray); $i++)
                        {
                            $newPinnedList .= "," . $pinnedArray[$i];
                        }
                    }

                    $keepUsers[] = $userInfo[0] . "|" . $userInfo[1] . "|" . $userInfo[2] . "|" . $userInfo[3] . "|" . $newPinnedList . "|" . PHP_EOL;
                }
                else
                {
                    $keepUsers[] = $userInfo[0] . "|" . $userInfo[1] . "|" . $userInfo[2] . "|" . $userInfo[3] . "||" . PHP_EOL;
                }
            }
            else
            {
                if($userInfo[4] == "")
                {
                    $keepUsers[] = $userInfo[0] . "|" . $userInfo[1] . "|" . $userInfo[2] . "|" . $userInfo[3] . "|" . $itemId . "|" . PHP_EOL;
                }
                else
                {
                    $keepUsers[] = $userInfo[0] . "|" . $userInfo[1] . "|" . $userInfo[2] . "|" . $userInfo[3] . "|" . $userInfo[4] . "," . $itemId . "|" . PHP_EOL;
                }
            }
        }
        else
        {
            $keepUsers[] = $user;
        }
    }

    $userFile = fopen("users.txt", "w");

    foreach($keepUsers as $keepUser)
    {
        fwrite($userFile, $keepUser);
    }

    fclose($userFile);

    header("Location: index.php");
    exit();
}
else
{
    header("Location: index.php");
    exit();
}
