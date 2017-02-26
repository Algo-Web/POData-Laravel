<?php

namespace AlgoWeb\PODataLaravel\Controllers;

use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Models\TestMorphTarget;
use AlgoWeb\PODataLaravel\Requests\TestRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class ElectricBoogalooController extends \Illuminate\Routing\Controller
{
    use MetadataControllerTrait;

    public function __construct()
    {
        $this->mapping = [
            TestMorphTarget::class =>
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
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function storeTestModel(TestRequest $request)
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
            $error = 'No query results for model [AlgoWeb\PODataLaravel\Models\TestModel] 0';
            $errors = new \Illuminate\Support\MessageBag([$error]);
            return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
        }
        return response()->json(['status' => 'error', 'id' => null, 'errors' => $validator->errors()]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function showTestModel($id)
    {
        $isSuccess = 0 < $id;

        if ($isSuccess) {
            return response()->json(['status' => 'success', 'id' => $id, 'errors' => null]);
        }
        $error = 'No query results for model [AlgoWeb\PODataLaravel\Models\TestModel] '.$id;
        $errors = new \Illuminate\Support\MessageBag([$error]);
        return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function updateTestModel(TestRequest $request, $id)
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
            $err = 'No query results for model [AlgoWeb\PODataLaravel\Models\TestModel] '.$id;
            $errors = new \Illuminate\Support\MessageBag([$err]);
            return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
        }
        return response()->json(['status' => 'error', 'id' => null, 'errors' => $validator->errors()]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroyTestModel($id)
    {
        $isSuccess = 0 < $id;

        if ($isSuccess) {
            return response()->json(['status' => 'success', 'id' => $id, 'errors' => null]);
        }
        $error = 'No query results for model [AlgoWeb\PODataLaravel\Models\TestModel] '.$id;
        $errors = new \Illuminate\Support\MessageBag([$error]);
        return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
    }
}
