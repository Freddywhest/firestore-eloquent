<?php
namespace Roddy\FirestoreEloquent\Facade;

use Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass;

/**
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass where(array $collection)
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass get()
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass first()
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass firstOrFail()
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass find($id)
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass all()
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass paginate(int $limit, string $name = 'page')
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass orderBy(string $path, ?string $direction = null)
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass orderByAsc(string $path)
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass andWhere(array $filters)
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass orWhere(array $filters)
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass updateMany(array $data)
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass deleteMany()
 * @method static \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass create(array $data, $id = '')
 * @see \Roddy\FirestoreEloquent\Firestore\Eloquent\FirestoreModelClass
*/
class FModel
{
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
        private $model;
        protected $primaryKey = 'id';
        protected $fillable = [];
        protected $required = [];
        protected $default = [];
        protected $fieldTypes = [];

    public function __construct()
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
        $className = explode('\\', $this::class);
        if(!$this->collection){
            $this->collection = end($className).'s';
        }

        /**
         * Set the model name.
         */
        $this->model = end($className);
    }

    /**
     * Magic method to retrieve the value of the primary key attribute.
     *
     * @param string $primaryKey The name of the primary key attribute.
     * @return mixed The value of the primary key attribute.
     */
    public function __get($name)
    {
        if(in_array($name, ['primaryKey', 'fillable', 'required', 'default', 'fieldTypes'])){
            return $this->{$name};
        }
    }


    public static function __callStatic($name, $arguments)
    {
        try {
            return (new FirestoreModelClass(
                collection: (new static)->collection,
                model: (new static)->model,
                primaryKey: (new static)->primaryKey,
                fillable: (new static)->fillable,
                required: (new static)->required,
                default: (new static)->default,
                fieldTypes: (new static)->fieldTypes
            ))->$name(...$arguments);
        } catch (\Throwable $th) {
            throw new \Exception("Call to undefined method ".static::class."::".$name."().". ' Main Error:- ' .$th->getMessage(), 1);

        }
    }
}
