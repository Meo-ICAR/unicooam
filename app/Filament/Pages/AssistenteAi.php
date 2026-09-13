<?php

namespace App\Filament\Pages;

use App\Mail\AssistantEscalationMail;
use App\Models\User;
use App\Neuron\ManualAssistantAgent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;
use NeuronAI\Chat\Messages\Usage;
use NeuronAI\Chat\Messages\UserMessage;
use Throwable;

/**
 * Assistente AI che risponde a domande sull'uso dell'applicazione, indicizzato
 * da `php artisan manual:sync` (vedi ManualAssistantAgent).
 *
 * @property-read Schema $form
 */
class AssistenteAi extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected string $view = 'filament.pages.assistente-ai';

    protected static ?string $navigationLabel = 'Assistente AI';

    protected static ?string $title = 'Assistente AI';

    protected static ?string $slug = 'assistente-ai';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?string $answer = null;

    public ?string $error = null;

    public ?int $inputTokens = null;

    public ?int $outputTokens = null;

    public ?int $cachedInputTokens = null;

    public ?string $lastPrompt = null;

    public ?string $escalationCode = null;

    public ?string $escalationError = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Textarea::make('prompt')
                        ->label('Chiedi all\'Assistente AI')
                        ->placeholder('Es: come emetto un proforma?')
                        ->rows(3)
                        ->autosize()
                        ->required(),
                ])
                    ->id('assistente-ai-form')
                    ->livewireSubmitHandler('send')
                    ->footer([
                        Actions::make([
                            Action::make('send')
                                ->label('Chiedi')
                                ->icon('heroicon-o-paper-airplane')
                                ->submit('assistente-ai-form'),
                            Action::make('escalate')
                                ->label('Non è la risposta che cercavo, contatta il supporto')
                                ->icon('heroicon-o-lifebuoy')
                                ->color('warning')
                                ->requiresConfirmation()
                                ->modalDescription('Verrà inviata una email al supporto con la domanda posta e la risposta ricevuta, per conoscenza anche a te.')
                                ->visible(fn () => blank($this->escalationCode) && (filled($this->answer) || filled($this->error)))
                                ->action(fn () => $this->escalate()),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $this->error = null;
        $this->answer = null;
        $this->inputTokens = null;
        $this->outputTokens = null;
        $this->cachedInputTokens = null;
        $this->escalationCode = null;
        $this->escalationError = null;

        $prompt = $this->form->getState()['prompt'] ?? null;

        if (blank($prompt)) {
            return;
        }

        $this->lastPrompt = $prompt;

        try {
            $reply = ManualAssistantAgent::make()->chat(new UserMessage($prompt))->getMessage();
            $this->answer = (string) $reply->getContent();

            $usage = $reply->getUsage();

            if ($usage instanceof Usage) {
                $this->inputTokens = $usage->inputTokens;
                $this->outputTokens = $usage->outputTokens;
                $this->cachedInputTokens = $usage->cachedInputTokens;
            }
        } catch (Throwable $e) {
            $this->error = "Non riesco a rispondere in questo momento: {$e->getMessage()}";
        }
    }

    public function escalate(): void
    {
        $this->escalationError = null;

        if (blank($this->lastPrompt) || (blank($this->answer) && blank($this->error))) {
            return;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $code = now()->format('Y-m-d H:i');

        try {
            Mail::to('info@hassisto.com')
                ->cc($user->email)
                ->send(new AssistantEscalationMail(
                    code: $code,
                    user: $user,
                    prompt: $this->lastPrompt,
                    answer: $this->answer,
                    error: $this->error,
                ));

            $this->escalationCode = $code;
        } catch (Throwable $e) {
            $this->escalationError = "Impossibile inviare la richiesta al supporto: {$e->getMessage()}";
        }
    }
}
