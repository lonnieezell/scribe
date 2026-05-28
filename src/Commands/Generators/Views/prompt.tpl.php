<@php

declare(strict_types=1);

namespace {namespace};

use Myth\Scribe\Prompts\BasePrompt;

class {class} extends BasePrompt
{
    /**
     * The static system prompt that sets the AI's role and behavior.
     * Think of this as the "instructions" given to the AI before the conversation starts.
     * Example: "You are a helpful assistant that summarizes text concisely."
     */
    public function systemPrompt(): string
    {
        return '';
    }

    /**
     * The dynamic user message sent to the AI for this request.
     * This is where you inject runtime data (e.g. the text to summarize).
     * Example: "Summarize the following: {$this->text}"
     */
    public function userPrompt(): string
    {
        return '';
    }
}
