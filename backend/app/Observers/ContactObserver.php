<?php

namespace App\Observers;

use App\Models\Contact;
use App\Services\ActivityService;
use App\Services\WorkflowTriggerService;

class ContactObserver
{
    public function created(Contact $contact): void
    {
        ActivityService::log($contact, "Contact created: {$contact->first_name} {$contact->last_name}");
        app(WorkflowTriggerService::class)->evaluateTriggers('contact_created', $contact, []);
    }

    public function updated(Contact $contact): void
    {
        ActivityService::log($contact, "Contact updated: {$contact->first_name} {$contact->last_name}");
    }

    public function deleted(Contact $contact): void
    {
        ActivityService::log($contact, "Contact deleted: {$contact->first_name} {$contact->last_name}");
    }
}
