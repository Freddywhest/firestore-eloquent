<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent;

use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\AllTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\AndWhereTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\CreateTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\DeleteManyTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\FindTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\FirestoreConnectionTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\FirstOrFailTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\FirstTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\GetTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\OrWhereTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\PaginateTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\UpdateManyTrait;
use Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\WhereTrait;

class FirestoreModelClass
{
    use FirestoreConnectionTrait,
    GetTrait,
    FirstTrait,
    FirstOrFailTrait,
    DeleteManyTrait,
    OrWhereTrait,
    AndWhereTrait,
    WhereTrait,
    FindTrait,
    PaginateTrait,
    AllTrait;
    use CreateTrait, UpdateManyTrait;

    /**
     * @property string $collection
     * @property mixed $model
     * @property string $primaryKey
     * @property array $fillable
     * @property array $required
     * @property array $default
     * @property array $fieldTypes
     */

     protected $collection;
     private  $filter;
     private  $query;
     private  $direction;
     private  $path;
     private $model;
     protected $primaryKey = 'id';
     protected $fillable = [];
     protected $required = [];
     protected $default = [];
     protected $fieldTypes = [];

    public function service()
    {
        return new $this;
    }

    public function __construct(
        $collection,
        $primaryKey,
        $fillable,
        $required,
        $default,
        $fieldTypes,
        $model,
    )
    {
        /**
         * Check if FIREBASE_PROJECT_ID is set in .env file or config file.
         * If not set, throw an exception.
         */
        if(!config('firebase.projects.app.project_id', env('FIREBASE_PROJECT_ID'))){
            throw new \Exception("FIREBASE_PROJECT_ID not set in .env file.");
        }

        /**
         * Get the class name and set the collection name if not already set.
         */
        $this->collection = $collection;
        $this->primaryKey = $primaryKey;
        $this->fillable = $fillable;
        $this->required = $required;
        $this->default = $default;
        $this->fieldTypes = $fieldTypes;
        $this->model = $model;
    }

    /**
     * Retrieve all documents from the Firestore database matching the current query parameters.
     *
     * @return array The list of documents matching the current query parameters.
     * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\GetTrait::fget()
     *
     * @example
     * ```php
     * $users = User::where(['age' => 20])->get(); // Retrieve all users with age 20
     * ```
     */
    public function get()
    {

        return $this->fget(
            path: $this->path,
            direction: $this->direction,
            query: $this->query,
            model: $this->model,
            collection: $this->collection
        );
    }

    /**
     * Retrieve the first document from the Firestore database matching the current query parameters.
     *
     * @return mixed The first document matching the current query parameters.
     * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\FirstTrait::fFirst()
     *
     * @example
     * ```php
     * $user = User::where(['age' => 20])->first(); // Retrieve the first user with age 20
     * ```
     */
    public function first()
    {
        return $this->fFirst(
            path: $this->path,
            direction: $this->direction,
            query: $this->query,
            collection: $this->collection,
            model: $this->model
        );
    }

    /**
     * Retrieve the first document from the Firestore database matching the current query parameters or throw an exception if no document is found.
     *
     * @return mixed The first document matching the current query parameters.
     * @throws \Exception If no document is found.
     * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\FirstOrFailTrait::fFirstOrFail()
     *
     * @example
     * ```php
     * $user = User::where(['age' => 20])->firstOrFail(); // Retrieve the first user with age 20 or throw an exception if no user is found
     * ```
     */
    public function firstOrFail()
    {
        return $this->fFirstOrFail(
            path: $this->path,
            direction: $this->direction,
            query: $this->query,
            collection: $this->collection,
            model: $this->model
        );
    }

    /**
     * Retrieve all documents from the Firestore database .
     *
     * @return array The list of documents.
     * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\AllTrait::fAll()
     *
     * @example
     * ```php
     * $users = User::all(); // Retrieve all users
     * ```
     */
    public  function all()
    {

        return $this->fAll(
            path: $this->path,
            direction: $this->direction,
            model: $this->model,
            collection: $this->collection,
            firestore: $this->fConnection($this->collection)
        );
    }

    /**
     * Retrieve a document from the Firestore database by its document ID.
     *
     * @param string $documentId The ID of the document to retrieve.
     * @return \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreDataFormat The document with the given ID.
     * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\FindTrait::fFind()
     *
     * @example
     * ```php
     * $documentId = '1234567890'; // The ID of the document to retrieve
     * $user = User::find($documentId); // Retrieve the document with the given ID
     * ```
     */
    public  function find(string $documentId)
    {

        return $this->fFind(
            documentId: $documentId,
            collection: $this->collection,
            firestore: $this->fConnection($this->collection),
            model: $this->model
        );
    }

    /**
     * Creates a new query instance with the given filter.
     *
     * @param array $filter The filter to apply to the query.
     * @return  Returns a new instance of the model with the query applied.
     * @throws \Exception
     * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\WhereTrait::fWhere()
     *
     * @example
     * ```php
     * $users = User::where(['age' => 20])->get(); // Get all users with age 20
     * ```
     */
    public  function where(array $filter)
    {

        $this->query = $this->fWhere(filter: $filter, firestore: $this->fConnection($this->collection));
        return $this;

    }

    /**
     * Set the order by clause for the query.
     *
     * @param string $path The field path to order by.
     * @param string|null $direction The direction of the ordering. Either "DESC" or "ASC".
     * @return
     * @throws \Exception If the direction is not "DESC" or "ASC".
     *
     * @example
     * ```php
     * $users = User::where(["age" => 25])->orderBy('id', 'DESC')->get(); // Get all users with age 25 ordered by id descending
     * ```
     * @example
     * ```php
     * $users = User::where(["age" => 25])->orderBy('id')->get(); // Get all users with age 25 ordered by id ascending
     * ```
     * @example
     * ```php
     * $users = User::orderBy('id')->all(); // Get all users ordered by id ascending
     * ```
     */
    public  function orderBy(string $path, ?string $direction = null)
    {

        if($direction && !in_array(strtoupper($direction), ['DESC', 'ASC'])){
            throw new \Exception('OrderBy direction should be either "DESC" or "ASC"', 1);
        }

        $this->path = $path;
        $this->direction = $direction;

        return $this;
    }

    /**
     * Set the order of the query results to descending based on the given field path.
     *
     * @param string $path The field path to order by.
     * @return
     *
     * @example
     * ```php
     * $users = User::orderByDesc('id')->all(); // Get all users ordered by id descending
     * ```
     * @example
     * ```php
     * $users = User::where(["age" => 25])->orderByDesc('id')->get(); // Get all users with age 25 ordered by id descending
     * ```
     */
    public  function orderByDesc(string $path)
    {

        $this->path = $path;
        $this->direction = 'DESC';

        return $this;
    }

    /**
     * Set the order of the query results to ascending based on the given field path.
     *
     * @param string $path The field path to order by.
     * @return
     *
     * @example
     * ```php
     * $users = User::orderByAsc('id')->all(); // Get all users ordered by id ascending
     * ```
     * @example
     * ```php
     * $users = User::where(["age" => 25])->orderByAsc('id')->get(); // Get all users with age 25 ordered by id ascending
     * ```
     */
    public  function orderByAsc(string $path)
    {

        $this->path = $path;
        $this->direction = 'ASC';

        return $this;
    }

    /**
     * Adds AND WHERE clauses to the query based on the provided filters.
     *
     * @param array $filters The filters to apply to the query.
     * @return
     *
     * @throws \Exception
     *
     * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\AndWhereTrait::fAndWhere();
     *
     * @example
     * ```php
     * $users = User::andWhere([['name', '==', 'John'], ['age' => 25]])->get(); // Get all users with name John and age 25
     * ```
     */
    public  function andWhere(array $filters)
    {

        $this->query = $this->fAndWhere(filters: $filters, firestore: $this->fConnection($this->collection));
        return $this;
    }

    /**
     * Adds OR WHERE clauses to the query based on the provided filters.
     *
     * @param array $filters The filters to apply to the query.
     * @return
     *
     * @throws \Exception
     *
     * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\OrWhereTrait::fOrWhere();
     *
     * @example
     * ```php
     * $users = User::orWhere([['name', '==', 'John'], ['age' => 25]])->get(); // Get all users with name John or age 25
     * ```
     */
    public  function orWhere(array $filters)
    {

        $this->query = $this->fOrWhere(filters: $filters, firestore: $this->fConnection($this->collection));
        return $this;
    }

    /**
     * Update multiple documents in Firestore collection.
     *
     * @param array $data The data to update.
     * @return void
     *
     * @throws \Exception
     *
     * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\UpdateManyTrait::fUpdateMany()
     * @example
     * ```php
     * User::where(['age' => 20])->updateMany(['age' => 25]); // Update all users with age 20
     * ```
     * @example
     * ```php
     * User::where(['age' => 20])->updateMany(['age' => 25, 'name' => 'John']); // Update all users with age 20
     * ```
     * @example
     * ```php
     * User::where(['age' => 20])->updateMany(['age' => 25, 'name' => 'John', 'dob' => '1990-01-01']); // Update all users with age 20
     * ```
     */
    public  function updateMany(array $data)
    {

        return $this->fUpdateMany(data: $data,
                    query: $this->query,
                    primaryKey: $this->primaryKey,
                    fillable: $this->fillable,
                    required: $this->required,
                    fieldTypes: $this->fieldTypes,
                    model: $this->model,
                    collection: $this->collection
                );
    }

    /**
     * Delete multiple documents from Firestore collection.
     *
     * @return void
     *
     * @throws \Exception
     *
     * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\DeleteManyTrait::fDeleteMany()
     *
     * @example
     * ```php
     * User::where(['age' => 20])->deleteMany(); // Delete all users with age 20
     * ```
     */
    public  function deleteMany()
    {
        if(!$this->query){
            $query = $this->fConnection($this->collection);
        }else{
            $query = $this->query;
        }

        return $this->fDeleteMany(query: $query);
    }

    /**
     * Create a new document in Firestore collection.
     *
     * @param array $data The data to create the document with.
     * @param string $id The ID of the document to create.
     * @return object/document Created document.
     *
     * @throws \Exception
     *
     * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\QueryHelpers\CreateTrait::fCreate()
     *
     * @example
     * ```php
     * $newUser = User::create(['name' => 'John', 'age' => 20]); // Create a new user
     * $newUser->name; // John
     * ```
     */
    public  function create(array $data)
    {
        return $this->fCreate(data: $data,
            firestore: $this->fConnection($this->collection),
            fillable: $this->fillable,
            required: $this->required,
            default: $this->default,
            model: $this->model,
            primaryKey: $this->primaryKey,
            fieldTypes: $this->fieldTypes
        );
    }
    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int $limit The number of items per page
     * @param string $name The name of the page query parameter
     * @return object
     *
     * @example
     * ```php
     * $data = User::paginate(10); // Paginate all users with 10 items per page
     * $user = $data->data();
     * ```
     */

    public  function paginate(int $limit, string $name = 'page'): object
    {
        if(!$this->query){
            $query = $this->fConnection($this->collection);
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


    /**
     * Get the count of the data.
     *
     * @return int
     */
    public function count()
    {
        if(!$this->query){
            $query = $this->fConnection($this->collection);
        }else{
            $query = $this->query;
        }
        return $query->count();
    }

    public function limit($number)
    {
        if(!$this->query){
            if($this->path){
                if(!$this->direction){
                    $query = $this->fConnection($this->collection)->orderBy($this->path)->limit($number);
                }else{
                    $query = $this->fConnection($this->collection)->orderBy($this->path, $this->direction)->limit($number);
                }
            }else{
                $query = $this->fConnection($this->collection)->limit($number);
            }
        }else{
            if($this->path){
                if(!$this->direction){
                    $query = $this->query->orderBy($this->path)->limit($number);
                }else{
                    $query = $this->query->orderBy($this->path, $this->direction)->limit($number);
                }
            }else{
                $query = $this->query->limit($number);
            }
        }

        if($number == 1){
            foreach($query->documents()->rows() as $row){
                return new FirestoreDataFormat(
                    row: $row,
                    collectionName: $this->collection,
                    model: $this->model
                );
            }
        }else{
            $result = [];
            foreach($query->documents()->rows() as $row){
                array_push($result, new FirestoreDataFormat(
                    row: $row,
                    collectionName: $this->collection,
                    model: $this->model
                ));
            }
            return $result;
        }
    }
}
