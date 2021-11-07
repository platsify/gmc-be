<?php

namespace App\Http\Controllers;

use App\Jobs\SyncShopbase;
use App\Models\Project;
use App\Models\Shop;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Storage;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = isset($request->per_page) ? (int)$request->per_page : 10;
        $sort = $request->sort ?? 'id';
        $direction = isset($request->direction) ? strtoupper($request->direction) : 'DESC';

        $query = Shop::query();

        if (!empty($request->search)) {
            $search = is_numeric($request->search) ? (int)$request->search : $request->search;
            $colsToSearch = [
                'id',
                'name',
                'gmc_id'
            ];
            $query->where(function ($query) use ($colsToSearch, $search) {
                foreach ($colsToSearch as $colToSearch) {
                    $query->orWhere($colToSearch, $search)->orWhere($colToSearch, 'LIKE', '%' . $search . '%');
                }
            });
        }
        $query = $query->orderBy($sort, $direction);
        $query = $query->paginate($perPage);

        return response()->json($query);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
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
            'url' => 'string',
            'public_url' => 'string',
            'type' => 'required|numeric',
            'gmc_id' => 'numeric',
            'gmc_credential' => 'required',
            'api_key' => 'string',
            'api_secret' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => [$validator->getMessageBag()->toArray()]]);
        }

        $name = $request->input('name');
        $url = $request->input('url');
        $publicUrl = $request->input('public_url');
        $type = $request->input('type');
        $gmcId = $request->input('gmc_id');
        $gmcFileName = $request->file('gmc_credential')->getClientOriginalName();
        $gmcCredential = $request->file('gmc_credential')->storeAs('credentials', $gmcFileName);
        $apiKey = $request->input('api_key');
        $apiSecret = $request->input('api_secret');
        $active = $request->active;

        $isNewShop = false;
        if ($request->input('id')) {
            $shop = Shop::find($request->input('id'));
            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shop not found'
                ]);
            }

            $message = __('Cập nhật shop thành công');
        } else {
            $isNewShop = true;
            $shop = new Shop();
            $message = __('Thêm shop thành công');
        }

        $shop->name = $name;
        $shop->url = $url;
        $shop->public_url = $publicUrl;
        $shop->type = $type;
        $shop->gmc_id = $gmcId;
        $shop->gmc_credential = 'storage/app/credentials' . $gmcCredential;
        $shop->api_key = $apiKey;
        $shop->api_secret = $apiSecret;
        $shop->active = ($active || $active == "true") ? 1 : 0;
        $shop->save();

        if ($isNewShop) {
            SyncShopbase::dispatch($shop->id, 0);
        }
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $shop
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * //     * @return JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $dataToUpdate = Shop::find($id);

        if (!$dataToUpdate) {
            return response()->json([
                'status' => 'error',
                'message' => __('Không tìm thấy shop để cập nhật'),
            ]);
        }

        // Update data
        $dataToUpdate->update($request->except(['id', 'created_at', 'updated_at']));

        return response()->json([
            'status' => 'success',
            'message' => __('Cập nhật shop thành công'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $dataToUpdate = Shop::where('id', $id)->first();

        if ($dataToUpdate) {
            $dataToUpdate->delete();

            return response()->json([
                'status' => 'success',
                'message' => __('Xóa shop thành công')
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => __('Xóa shop thất bại')
        ]);
    }

    public function syncNow($shopId)
    {
        // TODO: Using ShopRepository
        $shop = Shop::where('_id', $shopId)->first();
        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found'
            ]);
        }

        if (!isset($shop->sync_status) && $shop->sync_status == Shop::SHOP_SYNC_RUNNING) {
            return response()->json([
                'success' => true,
                'message' => 'You submitted the request before, it has already been added to the queue'
            ]);
        }

        $lastSync = ($shop->last_sync) ?: 0;
        SyncShopbase::dispatch($shop->id, $lastSync);

        return response()->json([
            'success' => true,
            'data' => ''
        ]);
    }
}
