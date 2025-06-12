<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('orderDetails:id,order_id,product_id,qty,price,subtotal,variant_id')
            ->where('status', 'completed');
        $this->applySearchFilters($query, $request);
        $this->applySorting($query, $request);
        $invoices = $query->paginate(10);
        return res_paginate($invoices, 'Get Invoice', $invoices->items());
    }

    private function applySearchFilters($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'LIKE', "%{$search}%")
                    ->orWhere('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('address_to_receive', 'LIKE', "%{$search}%")
                    ->orWhere('city', 'LIKE', "%{$search}%")
                    ->orWhere('notes', 'LIKE', "%{$search}%");
            });
        }
        if ($request->filled('order_no')) {
            $query->where('order_no', 'LIKE', "%{$request->input('order_no')}%");
        }
        if ($request->filled('customer_name')) {
            $customerName = $request->input('customer_name');
            $query->where(function ($q) use ($customerName) {
                $q->where('first_name', 'LIKE', "%{$customerName}%")
                    ->orWhere('last_name', 'LIKE', "%{$customerName}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$customerName}%"]);
            });
        }
        if ($request->filled('email')) {
            $query->where('email', 'LIKE', "%{$request->input('email')}%");
        }
        if ($request->filled('phone')) {
            $query->where('phone', 'LIKE', "%{$request->input('phone')}%");
        }
        if ($request->filled('city')) {
            $query->where('city', 'LIKE', "%{$request->input('city')}%");
        }
        if ($request->filled('state')) {
            $query->where('state', 'LIKE', "%{$request->input('state')}%");
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }
        if ($request->filled('min_amount')) {
            $query->where('total_amount', '>=', $request->input('min_amount'));
        }

        if ($request->filled('max_amount')) {
            $query->where('total_amount', '<=', $request->input('max_amount'));
        }
        if ($request->filled('currency')) {
            $query->where('currency', $request->input('currency'));
        }
        if ($request->filled('date_from')) {
            $dateFrom = Carbon::parse($request->input('date_from'))->startOfDay();
            $query->where('order_date', '>=', $dateFrom);
        }
        if ($request->filled('date_to')) {
            $dateTo = Carbon::parse($request->input('date_to'))->endOfDay();
            $query->where('order_date', '<=', $dateTo);
        }
        if ($request->filled('date_range')) {
            $this->applyDateRange($query, $request->input('date_range'));
        }
        if ($request->filled('has_discount')) {
            if ($request->boolean('has_discount')) {
                $query->where('discount_amount', '>', 0);
            } else {
                $query->where('discount_amount', '=', 0);
            }
        }
        if ($request->filled('product_id')) {
            $query->whereHas('orderDetails', function ($q) use ($request) {
                $q->where('product_id', $request->input('product_id'));
            });
        }
        if ($request->filled('min_qty') || $request->filled('max_qty')) {
            $query->whereHas('orderDetails', function ($q) use ($request) {
                if ($request->filled('min_qty')) {
                    $q->where('qty', '>=', $request->input('min_qty'));
                }
                if ($request->filled('max_qty')) {
                    $q->where('qty', '<=', $request->input('max_qty'));
                }
            });
        }
    }

    private function applyDateRange($query, $range)
    {
        $now = Carbon::now();

        switch ($range) {
            case 'today':
                $query->whereDate('order_date', $now->toDateString());
                break;
            case 'yesterday':
                $query->whereDate('order_date', $now->subDay()->toDateString());
                break;
            case 'this_week':
                $query->whereBetween('order_date', [
                    $now->startOfWeek()->toDateString(),
                    $now->endOfWeek()->toDateString()
                ]);
                break;
            case 'last_week':
                $query->whereBetween('order_date', [
                    $now->subWeek()->startOfWeek()->toDateString(),
                    $now->subWeek()->endOfWeek()->toDateString()
                ]);
                break;
            case 'this_month':
                $query->whereMonth('order_date', $now->month)
                    ->whereYear('order_date', $now->year);
                break;
            case 'last_month':
                $lastMonth = $now->subMonth();
                $query->whereMonth('order_date', $lastMonth->month)
                    ->whereYear('order_date', $lastMonth->year);
                break;
            case 'this_year':
                $query->whereYear('order_date', $now->year);
                break;
            case 'last_year':
                $query->whereYear('order_date', $now->subYear()->year);
                break;
        }
    }

    private function applySorting($query, Request $request)
    {
        $sortBy = $request->input('sort_by', 'order_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc'])
            ? strtolower($sortDirection)
            : 'desc';
        $allowedSortFields = [
            'id',
            'order_no',
            'first_name',
            'last_name',
            'email',
            'total_amount',
            'order_date',
            'payment_method',
            'payment_status',
            'city',
            'state',
            'discount_amount'
        ];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('order_date', 'desc');
        }
    }

    private function getPaginatedResults($query, Request $request)
    {
        $selectFields = 'id,order_no,first_name,last_name,email,phone,address_to_receive,city,state,payment_method,payment_status,discount_amount,total_amount,currency,status,order_date,notes';
        if ($request->filled('per_page') && $request->input('per_page') !== 'all') {
            $perPage = min($request->input('per_page', 15), 100); // Max 100 per page
            return $query->selectRaw($selectFields)->paginate($perPage);
        }
        return $query->selectRaw($selectFields)->get();
    }

    public function searchSuggestions(Request $request)
    {
        $field = $request->input('field');
        $term = $request->input('term');
        if (!$field || !$term) {
            return res_fail('Field and term are required');
        }
        $suggestions = [];
        switch ($field) {
            case 'customer_name':
                $suggestions = Order::selectRaw("CONCAT(first_name, ' ', last_name) as name")
                    ->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"])
                    ->distinct()
                    ->limit(10)
                    ->pluck('name');
                break;
            case 'email':
                $suggestions = Order::select('email')
                    ->where('email', 'LIKE', "%{$term}%")
                    ->distinct()
                    ->limit(10)
                    ->pluck('email');
                break;
            case 'city':
                $suggestions = Order::select('city')
                    ->where('city', 'LIKE', "%{$term}%")
                    ->distinct()
                    ->limit(10)
                    ->pluck('city');
                break;
            case 'order_no':
                $suggestions = Order::select('order_no')
                    ->where('order_no', 'LIKE', "%{$term}%")
                    ->distinct()
                    ->limit(10)
                    ->pluck('order_no');
                break;
        }

        return res_success('Search suggestions', $suggestions);
    }

    public function getFilterOptions()
    {
        $options = [
            'payment_methods' => Order::select('payment_method')
                ->distinct()
                ->whereNotNull('payment_method')
                ->pluck('payment_method'),
            'payment_statuses' => Order::select('payment_status')
                ->distinct()
                ->whereNotNull('payment_status')
                ->pluck('payment_status'),
            'cities' => Order::select('city')
                ->distinct()
                ->whereNotNull('city')
                ->pluck('city'),
            'states' => Order::select('state')
                ->distinct()
                ->whereNotNull('state')
                ->pluck('state'),

            'currencies' => Order::select('currency')
                ->distinct()
                ->whereNotNull('currency')
                ->pluck('currency'),
        ];

        return res_success('Filter options', $options);
    }

    public function export(Request $request)
    {
        $query = Order::with('orderDetails:id,order_id,product_id,qty,price,subtotal,variant_id')
            ->where('status', 'completed');
        $this->applySearchFilters($query, $request);
        $this->applySorting($query, $request);
        $invoices = $query->selectRaw('id,order_no,first_name,last_name,email,phone,address_to_receive,city,state,payment_method,payment_status,discount_amount,total_amount,currency,status,order_date,notes')
            ->get();
        return res_success('Export data', $invoices);
    }
}
