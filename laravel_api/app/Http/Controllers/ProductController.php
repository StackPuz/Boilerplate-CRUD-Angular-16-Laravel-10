<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Util;
use App\Models\Product;

class ProductController extends Controller {

    public function index()
    {
        $size = request()->input('size') ? request()->input('size') : 10;
        $sort = request()->input('sort') ? request()->input('sort') : 'Product.id';
        $sortDirection = request()->input('sort') ? (request()->input('desc') ? 'desc' : 'asc') : 'asc';
        $column = request()->input('sc');
        $query = Product::query()
            ->leftjoin('Brand', 'Product.brand_id', 'Brand.id')
            ->leftjoin('UserAccount', 'Product.create_user', 'UserAccount.id')
            ->select('Product.id', 'Product.image', 'Product.name', 'Product.price', 'Brand.name as brand_name', 'UserAccount.name as user_account_name')
            ->orderBy($sort, $sortDirection);
        if (Util::IsInvalidSearch($query->getQuery()->columns, $column)) {
            abort(403);
        }
        if (request()->input('sw')) {
            $search = request()->input('sw');
            $operator = Util::getOperator(request()->input('so'));
            if ($operator == 'like') {
                $search = '%'.$search.'%';
            }
            $query->where($column, $operator, $search);
        }
        $products = $query->paginate($size);
        return ['products' => $products->items(), 'last' => $products->lastPage()];
    }

    public function create()
    {
        $brands = DB::table('Brand')
            ->select('Brand.id', 'Brand.name')
            ->get();
        return ['brands' => $brands];
    }

    public function store()
    {
        $this->validate(request(), [
            'name' => 'unique:Product,name|required|max:50',
            'price' => 'required|max:10,2',
            'brand_id' => 'required',
            'image' => 'max:50'
        ]);
        $image = Util::getFile('products', request()->file('image'));
        Product::create([
            'name' => request()->input('name'),
            'price' => request()->input('price'),
            'brand_id' => request()->input('brand_id'),
            'image' => $image
            ,'create_user' => Auth::id()
            ,'create_date' => date('Y-m-d H:i:s')
        ]);
    }

    public function show($id)
    {
        $product = Product::query()
            ->leftjoin('Brand', 'Product.brand_id', 'Brand.id')
            ->leftjoin('UserAccount', 'Product.create_user', 'UserAccount.id')
            ->select('Product.id', 'Product.name', 'Product.price', 'Brand.name as brand_name', 'UserAccount.name as user_account_name', 'Product.image')
            ->where('Product.id', $id)
            ->first();
        return ['product' => $product];
    }

    public function edit($id)
    {
        $product = Product::query()
            ->select('Product.id', 'Product.name', 'Product.price', 'Product.brand_id', 'Product.image')
            ->where('Product.id', $id)
            ->first();
        $brands = DB::table('Brand')
            ->select('Brand.id', 'Brand.name')
            ->get();
        return ['product' => $product, 'brands' => $brands];
    }

    public function update($id)
    {
        $this->validate(request(), [
            'name' => 'required|max:50',
            'price' => 'required|max:10,2',
            'brand_id' => 'required',
            'image' => 'max:50'
        ]);
        $product = Product::find($id);
        $image = Util::getFile('products', request()->file('image')) ?? $product->image;
        Product::find($id)->update([
            'name' => request()->input('name'),
            'price' => request()->input('price'),
            'brand_id' => request()->input('brand_id'),
            'image' => $image
        ]);
    }

    public function destroy($id)
    {
        Product::find($id)->delete();
    }
}