<?php

namespace SafeTermMediaDelete;

class Wp_Core
{
    public function __construct(){
        new AddImageToTerm();
        new PreventImageDeletion();
        new RestAPIHook();
    }




}