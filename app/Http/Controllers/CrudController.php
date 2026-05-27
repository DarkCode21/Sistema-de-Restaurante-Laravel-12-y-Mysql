<?php

namespace App\Http\Controllers;

class CrudController extends Controller
{
    public function category() {
        
        return view('categories.index');
    }

    public function paymentMethod() {
        
        return view('paymentMethods.index');
    }

    public function table() {
        
        return view('tables.index');
    }

    public function product() {
        
        return view('products.index');
    }
}
