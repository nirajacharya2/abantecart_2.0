<?php

namespace abc\models\catalog;

use abc\core\ABC;
use abc\core\lib\AFile;
use abc\core\lib\AResourceManager;
use abc\models\BaseModel;
use Carbon\Carbon;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ResourceLibrary
 *
 * @property int $resource_id
 * @property int $type_id
 * @property int $stage_id
 * @property Carbon $date_added
 * @property Carbon $date_modified
 *
 * @property ResourceType $resource_type
 * @property Collection $resource_descriptions
 * @property Collection $resource_maps
 *
 * @method static ResourceLibrary find(int $resource_id) ResourceLibrary
 * @method static ResourceLibrary select(mixed $select) Builder
 *
 * @package abc\models
 */
class ResourceLibrary extends BaseModel
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['descriptions', 'maps'];

    protected $table = 'resource_library';
    protected $primaryKey = 'resource_id';
    public $timestamps = false;

    protected $casts = [
        'type_id' => 'int',
        'stage_id' => 'int',
    ];

    protected $dates = [
        'date_added',
        'date_modified',
    ];

    protected $fillable = [
        'type_id',
        'date_added',
        'date_modified',
        'stage_id'
    ];

    public function resource_type()
    {
        return $this->belongsTo(ResourceType::class, 'type_id');
    }

    public function descriptions()
    {
        return $this->hasMany(ResourceDescription::class, 'resource_id');
    }

    public function maps()
    {
        return $this->hasMany(ResourceMap::class, 'resource_id');
    }

    //add from URL download
    public function updateImageResourcesByUrls(
        $data = [],
        $object_txt_id = '',
        $object_id = 0,
        $title = '',
        $language_id = 1
    ) {
        $objects = [
            'products'             => 'Product',
            'product_option_value' => 'ProductOptionValue',
            'categories'           => 'Category',
            'manufacturers'        => 'Brand',
        ];

        if (!in_array($object_txt_id, array_keys($objects)) || !$data || !is_array($data)) {
            $this->errors[] = "Warning: Missing images for {$object_txt_id}.";
            return true;
        }

        $language_list = $this->registry->get('language')->getAvailableLanguages();
        /**
         * @var AResourceManager $rm
         */
        $rm = ABC::getObjectByAlias('AResourceManager');
        $rm->setType('image');

        //Remove previous resources of object
        $rm->unmapAndDeleteResources($object_txt_id, $object_id);

        //IMAGE PROCESSING
        $data['images'] = (array)$data['images'];
        foreach ($data['images'] as $source) {

            if (empty($source)) {
                continue;
            } else {
                if (is_array($source)) {
                    //we have an array from list of values. Run again
                    $this->updateImageResourcesByUrls(
                        ['images' => $source],
                        $object_txt_id,
                        $object_id,
                        $title,
                        $language_id
                    );
                    continue;
                }
            }
            //check if image is absolute path or remote URL
            $host = parse_url($source, PHP_URL_HOST);
            $image_basename = basename($source);
            $target = ABC::env('DIR_RESOURCES').$rm->getTypeDir().$image_basename;
            if (!is_dir(ABC::env('DIR_RESOURCES').$rm->getTypeDir())) {
                @mkdir(ABC::env('DIR_RESOURCES').$rm->getTypeDir(), 0777);
            }

            if ($host === null) {
                //this is a path to file
                if (!copy($source, $target)) {
                    $this->errors[] = "Error: Unable to copy file {$source} to {$target}";
                    continue;
                }
            } else {
                //this is URL to image. Download first
                $fl = new AFile();
                if (($file = $fl->downloadFile($source)) === false) {
                    $this->errors[] = "Error: Unable to download file from {$source} ";
                    continue;
                }

                if (!$fl->writeDownloadToFile($file, $target)) {
                    $this->errors[] = "Error: Unable to save downloaded file to ".$target;
                    continue;
                }
            }

            //save resource
            if ($title) {
                $titles = [];
                foreach ($language_list as $lang) {
                    $titles[$lang['language_id']] = $title;
                }
            } else {
                $titles = [];
            }

            $resource = [
                'language_id'   => $language_id,
                'name'          => $titles,
                'title'         => $titles,
                'description'   => '',
                'resource_path' => $image_basename,
                'resource_code' => '',
            ];
            foreach ($language_list as $lang) {
                $resource['name'][$lang['language_id']] = $title;
                $resource['title'][$lang['language_id']] = $title;
            }
            $resource_id = $rm->addResource($resource);
            if ($resource_id) {
                $rm->mapResource($object_txt_id, $object_id, $resource_id);
            } else {
                $this->errors[] = "Error: Image resource can not be created. ".$this->registry->get('db')->error;
            }
        }

        return true;
    }

}
