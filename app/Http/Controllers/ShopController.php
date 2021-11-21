<?php

namespace App\Http\Controllers;

use App\Jobs\SyncShopbase;
use App\Models\Project;
use App\Models\Shop;
use App\Repositories\Product\ProductRepositoryInterface;
use App\Repositories\RawProduct\RawProductRepositoryInterface;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Storage;
use App\Repositories\Shop\ShopRepositoryInterface;
use function Symfony\Component\Translation\t;

class ShopController extends Controller
{
    private $shopRepository;
    private $productRepository;
    private $rawProductRepository;

    public function __construct(ShopRepositoryInterface $shopRepository, ProductRepositoryInterface $productRepository, RawProductRepositoryInterface $rawProductRepository)
    {
        $this->shopRepository = $shopRepository;
        $this->productRepository = $productRepository;
        $this->rawProductRepository = $rawProductRepository;
    }

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

        $result = $query->paginate($perPage);

        return response()->json($result);
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
            'api_key' => 'string',
            'api_secret' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'errors' => [$validator->getMessageBag()->toArray()]]);
        }

        $isNewShop = false;
        if ($request->id) {
            $shop = $this->shopRepository->find($request->id);
            if (!$shop) {
                return response()->json(['success' => false, 'message' => 'Shop not found']);
            }
        } else {
            $isNewShop = true;
            $shop = new Shop();
            if (empty($request->gmc_credential)) {
                return response()->json(['success' => false, 'message' => 'gmc_credential is required']);
            }
        }

        $shop->fill($request->all());
        $shop->active = $request->active == 'true';
        if (!empty($request->gmc_credential) && (string)$request->gmc_credential !== 'null') {
            $gmcFileName = time().$request->file('gmc_credential')->getClientOriginalName();
            $shop->gmc_credential = $request->file('gmc_credential')->storeAs('credentials', $gmcFileName);
        } else {
            unset($shop->gmc_credential);
        }
        $shop->save();


        $message = __('Cập nhật shop thành công');
        if ($isNewShop) {
            $message = __('Thêm shop thành công');
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
     * @return JsonResponse
     */
    public function show($id)
    {
        $item = $this->shopRepository->find($id);
        if ($item) {
            return response()->json(['status' => 'success', 'data' => $item]);
        }
        return response()->json(['status' => 'error', 'message' => 'Shop not found']);

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
        if ($this->shopRepository->delete($id)) {
            // Xóa hết product, raw product
            $this->productRepository->deleteManyBySpecificField('shop_id', $id);
            $this->rawProductRepository->deleteManyBySpecificField('shop_id', $id);

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
                'status' => 'success',
                'message' => 'You submitted the request before, it has already been added to the queue'
            ]);
        }

        $lastSync = ($shop->last_sync) ?: 0;
        SyncShopbase::dispatch($shop->id, $lastSync);

        return response()->json([
            'status' => 'success',
            'data' => ''
        ]);
    }
}
