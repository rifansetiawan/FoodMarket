<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Food;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $name = $request->input('name');
        $types = $request->input('types');
        $price_from = $request->input('price_from');
        $price_to = $request->input('price_to');
        $rate_from = $request->input('rate_from');
        $rate_to = $request->input('rate_to');
        
        if($id)
        {
            $food = Food::find($id);
            
            if($food)
            {
                return ResponseFormatter::success($food, 'Data produk berhasil diambil');
            }
            else
            {
                return ResponseFormatter::error(null, 'Data Produk Tidak Ada', 404);
            }
        }
        
        $food = Food::query();
        
        if($name)
        {
            Food::query()->where('name', 'like' , '%' . 'name' . '%');
        }
        if($types)
        {
            Food::query()->where('name', 'like' , '%' . 'name' . '%');
        }
        if($price_from)
        {
            Food::query()->where('price', '>=' , $price_from);
        }
        if($price_to)
        {
            Food::query()->where('price', '<=' , $price_to);
        }
        if($rate_from)
        {
            Food::query()->where('rate', '>=' , $rate_from);
        }
        if($rate_to)
        {
            Food::query()->where('rate', '>=' , $rate_to);
        }
        
        return ResponseFormatter::success(
            $food->paginate($limit), 
            'Data berhasil diambil'
        );
    }
}