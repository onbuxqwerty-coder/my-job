<?php

declare(strict_types=1);

namespace Tests\Feature\Contacts;

use App\Enums\ContactRole;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function contacts_page_loads_successfully(): void
    {
        $this->get(route('contacts'))
            ->assertOk()
            ->assertSeeLivewire('contacts.contact-form');
    }

    #[Test]
    public function guest_can_submit_contact_form(): void
    {
        Volt::test('contacts.contact-form')
            ->set('name', 'Олексій Коваль')
            ->set('contact', 'oleksiy@example.com')
            ->set('role', 'seeker')
            ->set('topic', 'Технічна помилка')
            ->set('message', 'Не можу увійти в акаунт вже другий день')
            ->call('submit')
            ->assertSet('sent', true);

        $this->assertDatabaseHas('contact_messages', [
            'name'    => 'Олексій Коваль',
            'contact' => 'oleksiy@example.com',
            'role'    => 'seeker',
        ]);
    }

    #[Test]
    public function employer_message_saved_with_correct_role(): void
    {
        Volt::test('contacts.contact-form')
            ->set('name', 'ТОВ Рога і Копита')
            ->set('contact', 'hr@company.ua')
            ->set('role', 'employer')
            ->set('message', 'Цікавить корпоративний тариф для команди з 10 осіб')
            ->call('submit')
            ->assertSet('sent', true);

        $this->assertDatabaseHas('contact_messages', [
            'role' => ContactRole::Employer->value,
        ]);
    }

    #[Test]
    public function partnership_role_saved_correctly(): void
    {
        Volt::test('contacts.contact-form')
            ->set('name', 'Медіа партнер')
            ->set('contact', '@media_partner')
            ->set('role', 'partnership')
            ->set('message', 'Хочемо розмістити статтю про наш сервіс на вашій платформі')
            ->call('submit')
            ->assertSet('sent', true);

        $this->assertDatabaseHas('contact_messages', [
            'role' => ContactRole::Partnership->value,
        ]);
    }

    #[Test]
    public function validation_fails_when_name_missing(): void
    {
        Volt::test('contacts.contact-form')
            ->set('contact', 'test@example.com')
            ->set('role', 'seeker')
            ->set('message', 'Тестове повідомлення для перевірки валідації форми')
            ->call('submit')
            ->assertHasErrors(['name' => 'required']);
    }

    #[Test]
    public function validation_fails_when_message_too_short(): void
    {
        Volt::test('contacts.contact-form')
            ->set('name', 'Тест')
            ->set('contact', 'test@example.com')
            ->set('role', 'seeker')
            ->set('message', 'Коротко')
            ->call('submit')
            ->assertHasErrors(['message' => 'min']);
    }

    #[Test]
    public function topics_change_when_role_changes(): void
    {
        $component = Volt::test('contacts.contact-form')
            ->set('role', 'seeker');

        $this->assertContains(
            'Перегляд або фільтрація оголошень',
            $component->instance()->topics()
        );

        $component->set('role', 'employer');

        $this->assertContains(
            'Публікація або редагування вакансії',
            $component->instance()->topics()
        );
    }

    #[Test]
    public function recipient_email_matches_role(): void
    {
        $component = Volt::test('contacts.contact-form');

        $component->set('role', 'seeker');
        $this->assertEquals('support@myjob.co.ua', $component->instance()->recipientEmail());

        $component->set('role', 'employer');
        $this->assertEquals('sales@myjob.co.ua', $component->instance()->recipientEmail());

        $component->set('role', 'partnership');
        $this->assertEquals('partnership@myjob.co.ua', $component->instance()->recipientEmail());

        $component->set('role', 'other');
        $this->assertEquals('support@myjob.co.ua', $component->instance()->recipientEmail());
    }
}
