@component('mail::message')
# Ticket Approval Request

A new ticket requires your approval.

**Title:** {{ $ticket->title }}  
**Priority:** <span style="color: {{ $ticket->priority === 'high' ? '#dc2626' : ($ticket->priority === 'critical' ? '#991b1b' : '#2563eb') }}">{{ ucfirst($ticket->priority) }}</span>  
**Created By:** {{ $ticket->user->name }}  
**Due Date:** {{ $ticket->due_date ? $ticket->due_date->format('M d, Y') : 'Not set' }}

**Description:**  
{{ $ticket->description }}

@component('mail::button', ['url' => 'https://archangel.darth.cloud/admin/tickets'])
Review Ticket
@endcomponent

Thanks,  
{{ config('app.name') }}
@endcomponent