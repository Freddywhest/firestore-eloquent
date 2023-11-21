<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\SubCollectionTriat;

trait DeleteOne
{
    public function fdelete($collectionName, $documentId, $subCollectionName = null, $subCollectionId = null)
    {
        if($subCollectionName && $subCollectionId){
            $this->fConnection($collectionName)->document($documentId)->collection($subCollectionName)->document($subCollectionId)->delete();
        }else{
            $this->fConnection($collectionName)->document($documentId)->delete();
        }
    }
}
