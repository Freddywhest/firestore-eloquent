<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Illuminate\Support\Str;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\AllTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\AndWhereTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\DeleteManyTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\FirestoreConnectionTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\FirstOrFailTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\FirstTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\GetTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\OrWhereTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\PaginateTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\UpdateManyTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat\WhereTrait;

class SubDocumentModel
{
    use FirstOrFailTrait;
    use FirstTrait,
        WhereTrait,
        FirestoreConnectionTrait,
        OrWhereTrait,
        AndWhereTrait,
        PaginateTrait,
        DeleteManyTrait,
        GetTrait,
        AllTrait;
        use UpdateManyTrait;

    private $subCollectionName, $documentId, $model, $collection, $query;
    private $direction;
    private $path;

    public function __construct($subCollectionName, $documentId, $model, $collection)
    {
        $this->subCollectionName = $subCollectionName;
        $this->documentId = $documentId;
        $this->model = $model;
        $this->collection = $collection;
    }

    public function firstOrFail()
    {
        if(!$this->query){
            $query = $this->fConnection($this->collection)->document($this->documentId)->collection($this->subCollectionName);
        }else{
            $query = $this->query;
        }

        return $this->fFirstOrFail(
            query: $query,
            collection: $this->collection,
            model: $this->model,
            subCollection: $this->subCollectionName,
            documentIdForMainCollection: $this->documentId
        );
    }

    public function first()
    {
        if(!$this->query){
            $query = $this->fConnection($this->collection)->document($this->documentId)->collection($this->subCollectionName);
        }else{
            $query = $this->query;
        }

        return $this->fFirst(
            query: $query,
            collection: $this->collection,
            model: $this->model,
            subCollectionName: $this->subCollectionName,
            documentIdForMainCollection: $this->documentId
        );
    }

    public function where(array $filter)
    {
        $this->query = $this->fWhere(
            filter: $filter,
            firestore: $this->fConnection($this->collection),
            documentId: $this->documentId,
            collectionName: $this->subCollectionName
        );
        return $this;
    }

    public function orderBy(string $path, ?string $direction = null)
    {
        if($direction && !in_array(strtoupper($direction), ['DESC', 'ASC'])){
            throw new \Exception('OrderBy direction should be either "DESC" or "ASC"', 1);
        }

        $this->path = $path;
        $this->direction = $direction;

        return $this;
    }

    public function orderByDesc(string $path)
    {
        $this->path = $path;
        $this->direction = 'DESC';

        return $this;
    }

    public function orderByAsc(string $path)
    {
        $this->path = $path;
        $this->direction = 'ASC';

        return $this;
    }

    public function andWhere(array $filters)
    {
        $this->query = $this->fAndWhere(filters: $filters,
            firestore: $this->fConnection($this->collection),
            documentId: $this->documentId,
            collectionName: $this->subCollectionName
        );
        return $this;
    }

    public function orWhere(array $filters)
    {
        $this->query = $this->fOrWhere(filters: $filters,
            firestore: $this->fConnection($this->collection),
            documentId: $this->documentId,
            collectionName: $this->subCollectionName
        );
        return $this;
    }

    public function all()
    {
        return $this->fAll(
            path: $this->path,
            direction: $this->direction,
            model: $this->model,
            collection: $this->collection,
            firestore: $this->fConnection($this->collection),
            documentId: $this->documentId,
            collectionName: $this->subCollectionName,
            subCollectionName: $this->subCollectionName
        );
    }

    public function get()
    {
        return $this->fget(
            path: $this->path,
            direction: $this->direction,
            query: $this->query,
            model: $this->model,
            collection: $this->collection,
            documentId: $this->documentId,
            collectionName: $this->subCollectionName
        );
    }

    public function paginate(int $limit, string $name = 'page'): object
    {
        if(!$this->query){
            $query = $this->fConnection($this->collection)->document($this->documentId)->collection($this->subCollectionName)->count();
        }else{
            $query = $this->query;
        }

        return (object) $this->fPaginate(
            path: $this->path,
            direction: $this->direction,
            query: $query,
            model: $this->model,
            collection: $this->collection,
            name: $name,
            limit: $limit
        );
    }

    public function count()
    {
        if(!$this->query){
            return $this->fConnection($this->collection)->document($this->documentId)->collection($this->subCollectionName)->count();
        }

        return $this->query->count();
    }

    public function deleteMany()
    {
        if(!$this->query){
            $query = $this->fConnection($this->collection)->document($this->documentId)->collection($this->subCollectionName);
        }else{
            $query = $this->query;
        }

        $this->fDeleteMany(query: $query, firestore: $this->fConnection($this->collection)->document($this->documentId)->collection($this->subCollectionName));
    }

    public  function create(array $data, array $fillable = [], array $required = [], array $default = [], array $fieldTypes = [], $id = '')
    {

        /**
         * Get the namespace for the related model class.
         * If the 'firebase.class_namespace' configuration value is not set, the default namespace is 'App\FModels'.
         *
         * @return string The namespace for the related model class.
         */
        $namespace = config('firebase.class_namespace') ?? 'App\\FModels';

        /**
         * Explodes the fully qualified class name of the related model into an array.
         *
         * @param string $model The fully qualified class name of the related model.
         * @return array An array containing the exploded parts of the class name.
         */
        $modelArrName = explode('\\', $this->model);

        /**
         * Explodes the last element of the given array by '/' and returns the resulting array.
         *
         * @param array $modelArrName The array to be exploded.
         * @return array The resulting array after exploding the last element of the given array.
         */
        $modelArrName2 = explode('/', end($modelArrName));

        /**
         * Explodes the last element of the given model array name by '::' and returns the resulting array.
         */
        $modelArrName3 = explode('::', end($modelArrName2));

        /**
         * Explodes the last element of the given array by colon and returns the resulting array.
         *
         * @param array $modelArrName3 The input array to be processed.
         * @return array The resulting array after exploding the last element by colon.
         */
        $modelArrName4 = explode(':', end($modelArrName3));


        /**
         * Get the last element of the array $modelArrName4 and assign it to $modelName.
         */
        $modelName = end($modelArrName4);

        $class = $namespace.'\\'.$modelName;

        return $this->fCreate(data: $data, id: $id,
            firestore: $this->fConnection($this->collection),
            fillable: $fillable,
            required: $required,
            default: $default,
            model: $this->model,
            primaryKey: (new $class)->primaryKey,
            fieldTypes: $fieldTypes,
            documentId: $this->documentId,
            subCollectionName: $this->subCollectionName
        );
    }

    /**
     * Returns the type of the given value.
     *
     * @param mixed $value The value to check the type of.
     * @return string The type of the given value.
     */
    private function checkTypeInCreate($value)
    {
        return gettype($value);
    }

    private function fCreate(array $data, $id, $firestore, $fillable, $required, $default, $model, $primaryKey, $fieldTypes, $documentId, $subCollectionName)
    {
        $filteredData = [];

        if(count($fillable) > 0) {
            if(isset($data[$primaryKey])){
                unset($data[$primaryKey]);
            }

            if(count($default) > 0){
                foreach ($default as $k => $v) {
                    if(!isset($data[$k]) && in_array($k, $required) && in_array($k, $fillable) && $v){
                        $data[$k] = $v;
                    }else if(isset($data[$k]) && in_array($k, $required) && in_array($k, $fillable) && !$v){
                        $data[$k] = $v;
                    }
                }
            }

            foreach ($data as $key => $value) {
                if(count($fieldTypes) > 0){
                    if(isset($fieldTypes[$key])){
                        if($this->checkTypeInCreate($value) !== $fieldTypes[$key]){
                            throw new \Exception('"'.$key.'" expect type '.$fieldTypes[$key].' but got '.$this->checkTypeInCreate($value).'.', 1);
                        }
                    }
                }
                if(in_array($key, $fillable)){
                    if(in_array($key, $required) && !$value){
                        return throw new \Exception('"'.$key.'" is required.', 1);
                    }

                   $filteredData = array_merge($filteredData, [$key => $value]);
                }
            }

            if (count($required) > 0) {
                foreach ($required as $value) {
                    if(!isset($filteredData[$value])){
                        return throw new \Exception('"'.$value.'" is required.', 1);
                    }
                }
            }

            $collectionReference = $firestore;

            do {
                $id = Str::random(20);
            } while ($collectionReference->document($documentId)->collection($subCollectionName)->document($id)->snapshot()->exists());

            if(count($filteredData) > 0){
                $collectionReference->document($documentId)->collection($subCollectionName)->document($id)->set([...$filteredData, $primaryKey => $id]);

                $data = $collectionReference->document($documentId)->collection($subCollectionName)->document($id);

                return new class($data)
                {
                    private $data;
                    private $dataArr = [];

                    public function __construct($data)
                    {
                        $this->dataArr = $data->snapshot()->data();
                        $this->data = $data;
                    }

                    public function __get($name)
                    {
                        if(isset($this->dataArr[$name])){
                            return $this->dataArr[$name];
                        }else{
                            return null;
                        }
                    }

                    public function delete()
                    {
                        $this->data->delete();
                    }

                    public function exists()
                    {
                        return $this->data->snapshot()->exists();
                    }
                };
            }

            return new class($data)
            {
                public function __get($name)
                {
                    return null;
                }

                public function exists()
                {
                    return false;
                }
            };
        }else{
            return throw new \Exception('Cannot create a new "'.$model.'" because fillable property in "'.$model.'" model is empty or undefined.', 1);

        }
    }

    public function limit($number)
    {
        if(!$this->query){
            $query = $this->fConnection($this->collection)->document($this->documentId)->collection($this->subCollectionName);
        }else{
            $query = $this->query;
        }

        return $query->limit($number);
    }
}
