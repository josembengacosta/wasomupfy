<?php

function protecao()
{

    if (!isset($_SESSION['id_employees']) || !is_numeric($_SESSION['id_employees']) || !isset($_SESSION['business_employees']))
        header("Location:authentic/login?data=1");
}



?>