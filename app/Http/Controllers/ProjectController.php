<?php

namespace App\Http\Controllers;

use App\Jobs\FindProductsFromCategories;
use App\Jobs\SyncShopbase;
use App\Models\Product;
use App\Models\ProductMapCategory;
use App\Models\ProductMapProjects;
use App\Models\Project;
use App\Repositories\Product\ProductRepositoryInterface;
use App\Repositories\Project\ProjectRepositoryInterface;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    protected $productRepository;
    private $projectRepository;

    public function __construct(ProductRepositoryInterface $productRepository, ProjectRepositoryInterface $projectRepository)
    {
        $this->productRepository = $productRepository;
        $this->projectRepository = $projectRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $perPage = isset($request->per_page) ? (int)$request->per_page : 10;
        $sort = $request->sort ?? 'id';
        $direction = isset($request->direction) ? strtoupper($request->direction) : 'DESC';

        $query = Project::query();

        if (!empty($request->search)) {
            $search = is_numeric($request->search) ? (int)$request->search : $request->search;
            $colsToSearch = [
                'id',
                'name'
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
        $project = null;
        if ($request->input('_id')) {
            $project = $this->projectRepository->find($request->input('_id'));
        }
        if ($project) {
            $result = $this->projectRepository->update($request->_id, $request->all());
            return response()->json(['status' => 'success', 'data' => $result, 'message' => "Cập nhật thành công"]);
        } else {
            $result = $this->projectRepository->create($request->all());
            FindProductsFromCategories::dispatch($result->_id, $request->categories);
            return response()->json(['status' => 'success', 'data' => $result, 'message' => "Thêm thành công"]);
        }
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
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if ($this->projectRepository->delete($id)) {
            // TODO: Xóa hết product map project

            return response()->json([
                'status' => 'success',
                'message' => __('Xóa project thành công')
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => __('Xóa project thất bại')
        ]);
    }

}
