<?php

namespace abc\controllers\admin;

use abc\core\engine\AControllerAPI;
use abc\core\engine\Registry;
use abc\models\catalog\Manufacturer;
use abc\models\catalog\ResourceLibrary;

class ControllerApiCatalogManufacturer extends AControllerAPI
{
    public function get()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $request = $this->rest->getRequestParams();
        $this->data['request'] = $request;

        $getBy = null;
        if (isset($request['manufacturer_id']) && $request['manufacturer_id']) {
            $getBy = 'manufacturer_id';
        }
        if (isset($request['get_by']) && $request['get_by']) {
            $getBy = $request['get_by'];
        }

        if (!\H::has_value($getBy) || !isset($request[$getBy])) {
            $this->rest->setResponseData(['Error' => $getBy.' is missing']);
            $this->rest->sendResponse(200);
            return null;
        }
        $manufacturer = Manufacturer::where($getBy, '=', $request[$getBy])->get();

        if ($manufacturer === null) {
            $this->rest->setResponseData(
                ['Error' => "Manufacturer with ".$getBy." ".$request[$getBy]." does not exist"]
            );
            $this->rest->sendResponse(200);
            return null;
        }

        $this->data['result'] = [];
        /**
         * @var Manufacturer $item
         */
        $item = $manufacturer->first();
        if ($item) {
            $this->data['result'] = $item->getAllData();
        }
        if (!$this->data['result']) {
            $this->data['result'] = [
                'Error'  => 'Requested Manufacturer Not Found',
                'error_status' => 0,
            ];
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data['result']);
        $this->rest->sendResponse(200);
    }

    public function put()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        try {
            $request = $this->rest->getRequestParams();
            $this->data['request'] = $request;

            if (!is_array($this->data['request'])) {
                $this->rest->setResponseData(['Error' => 'Not correct input data']);
                $this->rest->sendResponse(200);
                return null;
            }

            $manufacturer = Manufacturer::addManufacturer($this->data['request']);

            $manufacturerObj = Manufacturer::find($manufacturer);
            if ($manufacturerObj && $this->data['fillable'] && is_array($this->data['fillable'])) {
                foreach ($this->data['fillable'] as $fillable){
                    if (!isset($request[$fillable])) {
                        continue;
                    }
                    $manufacturerObj->{$fillable} = $request[$fillable];
                    $manufacturerObj->save();
                }
            }

            if ($manufacturer) {
                $this->data['result']['manufacturer_id'] = $manufacturer;
                if (isset($this->data['request']['manufacturer_images'])) {
                    $manufacturerImages['images'] = $this->data['request']['manufacturer_images'];
                    $resource_mdl = new ResourceLibrary();
                    $resource_mdl->updateImageResourcesByUrls($manufacturerImages,
                        'manufacturers',
                        $manufacturer,
                        '',
                        $this->language->getContentLanguageID());
                }
            }
        } catch (\Exception $e) {
            $this->rest->setResponseData(['Error' => 'Create Error: '.$e->getMessage()]);
            $this->rest->sendResponse(200);
            return null;
        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data['result']);
        $this->rest->sendResponse(200);
    }

    public function post()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        $request = $this->rest->getRequestParams();
        try {

            $this->data['request'] = $request;

            //are we updating
            $updateBy = null;
            if (isset($request['manufacturer_id']) && $request['manufacturer_id']) {
                $updateBy = 'manufacturer_id';
            }
            if (isset($request['update_by']) && $request['update_by']) {
                $updateBy = $request['update_by'];
            }

            if ($updateBy) {
                $manufacturer = Manufacturer::where($updateBy, $request[$updateBy])->first();
                if ($manufacturer === null) {
                    $this->rest->setResponseData(
                        ['Error' => "manufacturer with {$updateBy}: {$request[$updateBy]} does not exist"]
                    );
                    $this->rest->sendResponse(200);
                    return null;
                }

                (new Manufacturer())->editManufacturer($manufacturer->manufacturer_id, $request);

                if ($this->data['fillable'] && is_array($this->data['fillable'])) {
                    foreach ($this->data['fillable'] as $fillable){
                        if (!isset($request[$fillable])) {
                            continue;
                        }
                        $manufacturer->{$fillable} = $request[$fillable];
                        $manufacturer->save();
                    }
                }

                //remove all mapped images if image array not set
                // made because http cannot send empty array!
                $request['manufacturer_images'] = $request['manufacturer_images'] ?: [];
                if (is_array($request['manufacturer_images'])) {
                    $manufacturerImages['images'] = $request['manufacturer_images'];
                    $resource_mdl = new ResourceLibrary();
                    $resource_mdl->updateImageResourcesByUrls($manufacturerImages,
                        'manufacturers',
                        $manufacturer->manufacturer_id,
                        '',
                        $this->language->getContentLanguageID());
                }
            }
        } catch (\PDOException $e) {
            $trace = $e->getTraceAsString();
            $this->log->error($e->getMessage());
            $this->log->error($trace);
            $this->rest->setResponseData(['Error' => $e->getMessage()]);
            $this->rest->sendResponse(200);
            return null;
        } catch (\Exception $e) {
            $this->rest->setResponseData(['Error' => $e->getMessage()]);
            $this->rest->sendResponse(200);
            return null;
        }

        Registry::cache()->flush();

        $this->data['result'] = [
            'status'      => $updateBy ? 'updated' : 'created',
            'manufacturer_id' => $manufacturer->manufacturer_id,
        ];

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data['result']);
        $this->rest->sendResponse(200);
    }

    public function delete()
    {
        $this->extensions->hk_InitData($this, __FUNCTION__);

        try {
            $request = $this->rest->getRequestParams();
            $this->data['request'] = $request;

            //are we updating
            $deleteBy = null;
            if (isset($request['manufacturer_id']) && $request['manufacturer_id']) {
                $deleteBy = 'manufacturer_id';
            }
            if (isset($request['delete_by']) && $request['delete_by']) {
                $deleteBy = $request['delete_by'];
            }

            if ($deleteBy) {
                Manufacturer::withTrashed()->where($deleteBy, $request[$deleteBy])
                    ->forceDelete();
                Registry::cache()->flush();
            } else {
                $this->rest->setResponseData(['Error' => 'Not correct request, manufacturer_id not found']);
                $this->rest->sendResponse(200);
                return null;
            }

        } catch (\Exception $e) {

        }

        $this->extensions->hk_UpdateData($this, __FUNCTION__);

        $this->rest->setResponseData($this->data['result']);
        $this->rest->sendResponse(200);
    }

}
