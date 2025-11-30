<?php

$password = "a2dgF2IzgW67CS";
echo hash('sha256', $password)."\n";
echo password_hash($password, PASSWORD_DEFAULT)."\n";