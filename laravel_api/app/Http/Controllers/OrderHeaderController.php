<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Util;
use App\Models\OrderHeader;

class OrderHeaderController extends Controller {

    public function index()
    {
        $size = request()->input('size') ? request()->input('size') : 10;
        $sort = request()->input('sort') ? request()->input('sort') : 'OrderHeader.id';
        $sortDirection = request()->input('sort') ? (request()->input('desc') ? 'desc' : 'asc') : 'asc';
        $column = request()->input('sc');
        $query = OrderHeader::query()
            ->leftjoin('Customer', 'OrderHeader.customer_id', 'Customer.id')
            ->select('OrderHeader.id', 'Customer.name as customer_name', 'OrderHeader.order_date')
            ->orderBy($sort, $sortDirection);
        if (Util::IsInvalidSearch($query->getQuery()->columns, $column)) {
            abort(403);
        }
        if (request()->input('sw')) {
            $search = request()->input('sw');
            $operator = Util::getOperator(request()->input('so'));
            if ($column == 'OrderHeader.order_date') {
                $search = Util::formatDateStr($search, 'date');
            }
            if ($operator == 'like') {
                $search = '%'.$search.'%';
            }
            $query->where($column, $operator, $search);
        }
        $orderHeaders = $query->paginate($size);
        return ['orderHeaders' => $orderHeaders->items(), 'last' => $orderHeaders->lastPage()];
    }

    public function create()
    {
        $customers = DB::table('Customer')
            ->select('Customer.id', 'Customer.name')
            ->get();
        return ['customers' => $customers];
    }

    public function store()
    {
        $this->validate(request(), [
            'customer_id' => 'required',
            'order_date' => 'required'
        ]);
        OrderHeader::create([
            'customer_id' => request()->input('customer_id'),
            'order_date' => Util::getDate(request()->input('order_date'))
        ]);
    }

    public function show($id)
    {
        $orderHeader = OrderHeader::query()
            ->leftjoin('Customer', 'OrderHeader.customer_id', 'Customer.id')
            ->select('OrderHeader.id', 'Customer.name as customer_name', 'OrderHeader.order_date')
            ->where('OrderHeader.id', $id)
            ->first();
        $orderHeaderOrderDetails = DB::table('OrderHeader')
            ->join('OrderDetail', 'OrderHeader.id', 'OrderDetail.order_id')
            ->join('Product', 'OrderDetail.product_id', 'Product.id')
            ->select('OrderDetail.no', 'Product.name as product_name', 'OrderDetail.qty')
            ->where('OrderHeader.id', $id)
            ->get();
        return ['orderHeader' => $orderHeader, 'orderHeaderOrderDetails' => $orderHeaderOrderDetails];
    }

    public function edit($id)
    {
        $orderHeader = OrderHeader::query()
            ->select('OrderHeader.id', 'OrderHeader.customer_id', 'OrderHeader.order_date')
            ->where('OrderHeader.id', $id)
            ->first();
        $orderHeaderOrderDetails = DB::table('OrderHeader')
            ->join('OrderDetail', 'OrderHeader.id', 'OrderDetail.order_id')
            ->join('Product', 'OrderDetail.product_id', 'Product.id')
            ->select('OrderDetail.no', 'Product.name as product_name', 'OrderDetail.qty', 'OrderDetail.order_id')
            ->where('OrderHeader.id', $id)
            ->get();
        $customers = DB::table('Customer')
            ->select('Customer.id', 'Customer.name')
            ->get();
        return ['orderHeader' => $orderHeader, 'orderHeaderOrderDetails' => $orderHeaderOrderDetails, 'customers' => $customers];
    }

    public function update($id)
    {
        $this->validate(request(), [
            'customer_id' => 'required',
            'order_date' => 'required'
        ]);
        OrderHeader::find($id)->update([
            'customer_id' => request()->input('customer_id'),
            'order_date' => Util::getDate(request()->input('order_date'))
        ]);
    }

    public function destroy($id)
    {
        OrderHeader::find($id)->delete();
    }
}