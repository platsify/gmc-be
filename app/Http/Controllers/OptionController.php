<?php

namespace App\Http\Controllers;

use App\Models\Option;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'value' => 'string|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => [$validator->getMessageBag()->toArray()]]);
        }

        $name = $request->input('name');
        $value = $request->input('value');

        $option = Option::updateOrCreate([
            'name' => $name
        ], [
            'value' => $value
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lưu thông tin thành công',
            'data' => $option
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Option $option
     * @return \Illuminate\Http\Response
     */
    public function show(Option $option)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param \App\Models\Option $option
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Option $option)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Option $option
     * @return \Illuminate\Http\Response
     */
    public function destroy(Option $option)
    {
        //
    }
}
