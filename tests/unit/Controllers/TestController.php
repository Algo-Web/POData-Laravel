<?php

namespace AlgoWeb\PODataLaravel\Controllers;

use AlgoWeb\PODataLaravel\Models\TestModel;
use AlgoWeb\PODataLaravel\Requests\TestRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class TestController extends \Illuminate\Routing\Controller
{
    use MetadataControllerTrait;

    public function __construct()
    {
        $this->mapping = [
            TestModel::class =>
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
            $model = new TestModel();
            foreach ($data as $key => $val) {
                $model->$key = $val;
            }
            $model->save();

            return response()->json(['status' => 'success', 'id' => $model->id, 'errors' => null]);
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
        $targModel = TestModel::find($id);

        if (isset($targModel)) {
            return response()->json(['status' => 'success', 'id' => $targModel->id, 'errors' => null]);
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
            $targModel = TestModel::find($id);
            if (isset($targModel)) {
                return response()->json(['status' => 'success', 'id' => $targModel->id, 'errors' => null]);
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
        $targModel = TestModel::find($id);

        if (isset($targModel)) {
            return response()->json(['status' => 'success', 'id' => $targModel->id, 'errors' => null]);
        }
        $error = 'No query results for model [AlgoWeb\PODataLaravel\Models\TestModel] '.$id;
        $errors = new \Illuminate\Support\MessageBag([$error]);
        return response()->json(['status' => 'error', 'id' => null, 'errors' => $errors]);
    }
}
