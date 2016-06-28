<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$configs = include '../config/config.php';

echo $configs['database'];

foreach ($configs as $key => $value) {
    echo $key . " => ". $value;
}