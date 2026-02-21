<?php

namespace Tests\Unit\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TicketPolicy();
    }

    /** @test */
    public function owner_can_view_any_ticket()
    {
        $owner = User::factory()->create();
        $this->assertTrue($this->policy->viewAny($owner));
    }

    /** @test */
    public function assignee_can_view_any_ticket()
    {
        $assignee = User::factory()->create();
        $this->assertTrue($this->policy->viewAny($assignee));
    }

    /** @test */
    public function other_user_can_view_any_ticket()
    {
        $otherUser = User::factory()->create();
        $this->assertTrue($this->policy->viewAny($otherUser));
    }

    /** @test */
    public function owner_can_view_their_ticket()
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertTrue($this->policy->view($owner, $ticket));
    }

    /** @test */
    public function assignee_can_view_their_assigned_ticket()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        $this->assertTrue($this->policy->view($assignee, $ticket));
    }

    /** @test */
    public function other_user_cannot_view_ticket()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertFalse($this->policy->view($otherUser, $ticket));
    }

    /** @test */
    public function owner_can_create_ticket()
    {
        $owner = User::factory()->create();
        $this->assertTrue($this->policy->create($owner));
    }

    /** @test */
    public function assignee_can_create_ticket()
    {
        $assignee = User::factory()->create();
        $this->assertTrue($this->policy->create($assignee));
    }

    /** @test */
    public function other_user_can_create_ticket()
    {
        $otherUser = User::factory()->create();
        $this->assertTrue($this->policy->create($otherUser));
    }

    /** @test */
    public function owner_can_update_their_ticket()
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertTrue($this->policy->update($owner, $ticket));
    }

    /** @test */
    public function assignee_cannot_update_ticket()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        $this->assertFalse($this->policy->update($assignee, $ticket));
    }

    /** @test */
    public function other_user_cannot_update_ticket()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertFalse($this->policy->update($otherUser, $ticket));
    }

    /** @test */
    public function owner_can_delete_their_ticket()
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertTrue($this->policy->delete($owner, $ticket));
    }

    /** @test */
    public function assignee_cannot_delete_ticket()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        $this->assertFalse($this->policy->delete($assignee, $ticket));
    }

    /** @test */
    public function other_user_cannot_delete_ticket()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertFalse($this->policy->delete($otherUser, $ticket));
    }

    /** @test */
    public function owner_can_change_status()
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertTrue($this->policy->changeStatus($owner, $ticket));
    }

    /** @test */
    public function assignee_can_change_status()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        $this->assertTrue($this->policy->changeStatus($assignee, $ticket));
    }

    /** @test */
    public function other_user_cannot_change_status()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertFalse($this->policy->changeStatus($otherUser, $ticket));
    }

    /** @test */
    public function owner_can_assign_ticket()
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertTrue($this->policy->assign($owner, $ticket));
    }

    /** @test */
    public function assignee_cannot_assign_ticket()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        $this->assertFalse($this->policy->assign($assignee, $ticket));
    }

    /** @test */
    public function other_user_cannot_assign_ticket()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertFalse($this->policy->assign($otherUser, $ticket));
    }

    /** @test */
    public function owner_can_add_message_to_their_ticket()
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertTrue($this->policy->addMessage($owner, $ticket));
    }

    /** @test */
    public function assignee_can_add_message_to_assigned_ticket()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        $this->assertTrue($this->policy->addMessage($assignee, $ticket));
    }

    /** @test */
    public function other_user_cannot_add_message_to_ticket()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertFalse($this->policy->addMessage($otherUser, $ticket));
    }

    /** @test */
    public function owner_can_upload_document_to_their_ticket()
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertTrue($this->policy->uploadDocument($owner, $ticket));
    }

    /** @test */
    public function assignee_can_upload_document_to_assigned_ticket()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        $this->assertTrue($this->policy->uploadDocument($assignee, $ticket));
    }

    /** @test */
    public function other_user_cannot_upload_document_to_ticket()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertFalse($this->policy->uploadDocument($otherUser, $ticket));
    }

    /** @test */
    public function owner_can_attach_document_to_their_ticket()
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertTrue($this->policy->attachDocument($owner, $ticket));
    }

    /** @test */
    public function assignee_can_attach_document_to_assigned_ticket()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        $this->assertTrue($this->policy->attachDocument($assignee, $ticket));
    }

    /** @test */
    public function other_user_cannot_attach_document_to_ticket()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertFalse($this->policy->attachDocument($otherUser, $ticket));
    }

    /** @test */
    public function owner_can_detach_document_from_their_ticket()
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertTrue($this->policy->detachDocument($owner, $ticket));
    }

    /** @test */
    public function assignee_can_detach_document_from_assigned_ticket()
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create(['assigned_to' => $assignee->id]);
        $this->assertTrue($this->policy->detachDocument($assignee, $ticket));
    }

    /** @test */
    public function other_user_cannot_detach_document_from_ticket()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'owner')->create();
        $this->assertFalse($this->policy->detachDocument($otherUser, $ticket));
    }
}
