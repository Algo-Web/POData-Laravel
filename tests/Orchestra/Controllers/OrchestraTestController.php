<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 11:47 PM.
 */
namespace AlgoWeb\PODataLaravel\Orchestra\Tests\Controllers;

use AlgoWeb\PODataLaravel\Orchestra\Tests\Models\OrchestraTestModel;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Request\OrchestraTestRequest;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Requests\TestBulkCreateRequest;
use AlgoWeb\PODataLaravel\Orchestra\Tests\Requests\TestBulkUpdateRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class OrchestraTestController extends Controller
{
    use \AlgoWeb\PODataLaravel\Controllers\MetadataControllerTrait;

    public function __construct()
    {
        $this->mapping = [
            OrchestraTestModel::class =>
                [
                    'create' => 'storeTestModel',
                    'read' => 'showTestModel',
                    'update' => 'updateTestModel',
                    'delete' => 'destroyTestModel'
                ]
        ];
    }

    public function setMapping(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request                                   $request
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\Response
     */
    public function storeTestModel(OrchestraTestRequest $request)
    {
        $data = $request->all();
        $rules = $request->rules();
        $msg = null;

        // Validate the inputs
        $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            $isSuccess = isset($data['success']) && true == $data['success'];

            if ($isSuccess) {
                return response()->json(['status' => 'success', 'id' => 1, 'errors' => null]);
            }
            $error = 'No query results for model [\Tests\AlgoWeb\PODataLaravel\Models\TestModel] 0';
            $errors = new \Illuminate\Support\MessageBag([$error]);
            return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
        }
        return response()->json(['status' => 'error', 'id' => null, 'errors' => $validator->errors()]);
    }

    /**
     * @param  TestBulkCreateRequest                                      $request
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeBulkTestModel(TestBulkCreateRequest $request)
    {
        $data = $request->all();
        $rules = $request->rules();
        $msg = null;

        // Validate the inputs
        $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            $bulkData = $data['data'];
            $isSuccess = true;
            $idList = [];
            foreach ($bulkData as $row) {
                $isSuccess &= isset($row['success']) && true == $row['success'];
                $idList[] = count($idList) + 1;
            }
            if ($isSuccess) {
                return response()->json(['status' => 'success', 'id' => $idList, 'errors' => null]);
            }

            $error = 'No query results for model [\Tests\AlgoWeb\PODataLaravel\Models\TestModel] 0';
            $errors = new \Illuminate\Support\MessageBag([$error]);
            return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
        }

        return response()->json(['status' => 'error', 'id' => null, 'errors' => $validator->errors()]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int                       $id
     * @return \Illuminate\Http\Response
     */
    public function showTestModel($id)
    {
        $isSuccess = 0 < $id;

        if ($isSuccess) {
            return response()->json(['status' => 'success', 'id' => $id, 'errors' => null]);
        }
        $error = 'No query results for model [\Tests\AlgoWeb\PODataLaravel\Models\TestModel] '.$id;
        $errors = new \Illuminate\Support\MessageBag([$error]);
        return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request                                   $request
     * @param  int                                                        $id
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\Response
     */
    public function updateTestModel(OrchestraTestRequest $request, $id)
    {
        $data = $request->all();
        $rules = $request->rules();
        $msg = null;

        // Validate the inputs
        $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            $isSuccess = isset($data['success']) && true == $data['success'];
            if ($isSuccess) {
                return response()->json(['status' => 'success', 'id' => $id, 'errors' => null]);
            }
            $err = 'No query results for model [\Tests\AlgoWeb\PODataLaravel\Models\TestModel] '.$id;
            $errors = new \Illuminate\Support\MessageBag([$err]);
            return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
        }
        return response()->json(['status' => 'error', 'id' => null, 'errors' => $validator->errors()]);
    }

    /**
     * @param  TestBulkUpdateRequest                                      $request
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBulkTestModel(TestBulkUpdateRequest $request)
    {
        $data = $request->all();
        $rules = $request->rules();
        $msg = null;

        // Validate the inputs
        $validator = Validator::make($data, $rules);

        if ($validator->passes()) {
            $bulkData = $data['data'];
            $bulkKeys = $data['keys'];
            $numKeys = count($bulkKeys);
            $isSuccess = true;
            $idList = [];

            for ($i = 0; $i < $numKeys; $i ++) {
                $row = $bulkData[$i];
                $rawKey = $bulkKeys[$i];

                $isSuccess &= isset($row['success']) && true == $row['success'];
                $idList[] = $rawKey['id'];
            }
            if ($isSuccess) {
                return response()->json(['status' => 'success', 'id' => $idList, 'errors' => null]);
            }

            $error = 'No query results for model [\Tests\AlgoWeb\PODataLaravel\Models\TestModel] 0';
            $errors = new \Illuminate\Support\MessageBag([$error]);
            return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
        }

        return response()->json(['status' => 'error', 'id' => null, 'errors' => $validator->errors()]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int                                                        $id
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Illuminate\Http\Response
     */
    public function destroyTestModel($id)
    {
        $isSuccess = 0 < $id;

        if ($isSuccess) {
            return response()->json(['status' => 'success', 'id' => $id, 'errors' => null]);
        }
        $error = 'No query results for model [\Tests\AlgoWeb\PODataLaravel\Models\TestModel] '.$id;
        $errors = new \Illuminate\Support\MessageBag([$error]);
        return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
    }
}
