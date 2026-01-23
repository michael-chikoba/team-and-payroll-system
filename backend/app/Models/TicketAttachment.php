<?php
// app/Models/TicketAttachment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'ticket_id',
        'user_id',
        'comment_id',
        'file_name',
        'original_name',
        'mime_type',
        'path',
        'disk',
        'size',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = [
        'url',
        'download_url',
        'formatted_size',
        'icon_class',
        'is_image'
    ];

    /**
     * Relationships
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->select(['id', 'first_name', 'last_name', 'email']);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class);
    }

    /**
     * Accessors
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->path) return null;
        
        return \Storage::disk($this->disk)->url($this->path);
    }

    public function getDownloadUrlAttribute(): ?string
    {
        if (!$this->path) return null;
        
        return route('tickets.attachments.download', $this->uuid);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function getIconClassAttribute(): string
    {
        $mime = $this->mime_type;
        
        if (str_starts_with($mime, 'image/')) {
            return 'file-image';
        } elseif (str_starts_with($mime, 'video/')) {
            return 'file-video';
        } elseif (str_starts_with($mime, 'audio/')) {
            return 'file-audio';
        } elseif ($mime === 'application/pdf') {
            return 'file-pdf';
        } elseif (str_contains($mime, 'word') || str_contains($mime, 'document')) {
            return 'file-word';
        } elseif (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet')) {
            return 'file-excel';
        } elseif (str_contains($mime, 'powerpoint') || str_contains($mime, 'presentation')) {
            return 'file-powerpoint';
        } elseif (str_contains($mime, 'zip') || str_contains($mime, 'compressed')) {
            return 'file-archive';
        } else {
            return 'file';
        }
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Scopes
     */
    public function scopeForTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeForComment($query, $commentId)
    {
        return $query->where('comment_id', $commentId);
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeDocuments($query)
    {
        return $query->where(function ($q) {
            $q->where('mime_type', 'like', 'application/%')
              ->orWhere('mime_type', 'like', 'text/%');
        });
    }
}