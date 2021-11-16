<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    private $categoryRepository;

    public function __construct(CategoryRepositoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = isset($request->per_page) ? (int)$request->per_page : 50;
        $sort = $request->sort ?? 'id';
        $direction = isset($request->direction) ? strtoupper($request->direction) : 'DESC';

        $query = Category::query();

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

    public function getCategoryByShop($shopId)
    {
        $items = $this->categoryRepository->findManyBySpecificField('shop_id', $shopId);
        return response()->json(['status' => 'success', 'data' => $items]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
