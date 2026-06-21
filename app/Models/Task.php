<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $orderBy = 'name';
    protected $orderDirection = 'asc';
    protected $fillable = ['name', 'description'];

    /**
     * I tipi di documento associati a questo Task
     */
    public function documentTypes(): BelongsToMany
    {
        return $this
            ->belongsToMany(DocumentType::class, 'task_document_types')
            ->withPivot('is_required')  // Permette di accedere al campo extra se serve
            ->withTimestamps();
    }
}
