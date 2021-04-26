<?php
    session_start();
    if (!file_exists('Server/files'))        
      mkdir('Server/files');
      require_once "Server/autoload.php";

    ApplicationInitBase::initConfigFree();
    ApplicationInitBase::initStorage();
    ApplicationInitBase::initDatabase();