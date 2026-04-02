<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Validation\Rules\Password;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = null;
    protected static string $view = 'filament.pages.profile';
    protected static ?string $title = 'Profile';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Change password')
                    ->description('Update your password.')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Current password')
                            ->password()
                            ->required()
                            ->currentPassword('admin')
                            ->revealable(),
                        Forms\Components\TextInput::make('password')
                            ->label('New password')
                            ->password()
                            ->required()
                            ->rule(Password::defaults())
                            ->same('password_confirmation')
                            ->revealable(),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirm new password')
                            ->password()
                            ->required()
                            ->revealable(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Update password')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Filament::auth()->user();

        $user->update([
            'password' => $data['password'],
        ]);

        $this->form->fill();
        Notification::make()
            ->title('Password updated successfully.')
            ->success()
            ->send();
    }
}
