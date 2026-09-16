<x-child-layout :child="$child">
    <div
        x-data="{
            prompt: '',
            answer: '',
            feedback: null,
            timeRemaining: {{ $session->planned_duration_seconds }},
            loading: true,
            totalPoints: {{ $session->total_points }},
            timerInterval: null,
            csrfToken: document.querySelector('meta[name=csrf-token]').content,
            nextQuestionUrl: '{{ route('child.sessions.next-question', $session) }}',
            attemptUrl: '{{ route('child.sessions.attempts', $session) }}',
            summaryUrl: '{{ route('child.sessions.summary', $session) }}',
            init() {
                this.timerInterval = setInterval(() => {
                    if (this.timeRemaining > 0) this.timeRemaining--;
                }, 1000);
                this.fetchNext();
            },
            minutes() { return Math.floor(this.timeRemaining / 60); },
            seconds() { return String(this.timeRemaining % 60).padStart(2, '0'); },
            async fetchNext() {
                this.loading = true;
                this.feedback = null;
                this.answer = '';
                const res = await fetch(this.nextQuestionUrl, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (data.session_over) { this.goToSummary(); return; }
                this.prompt = data.prompt;
                this.timeRemaining = data.time_remaining_seconds;
                this.loading = false;
            },
            press(digit) {
                if (this.loading || this.feedback) return;
                if (this.answer.length < 4) this.answer += digit;
            },
            backspace() {
                if (this.loading || this.feedback) return;
                this.answer = this.answer.slice(0, -1);
            },
            async submit() {
                if (this.answer === '' || this.loading || this.feedback) return;
                this.loading = true;
                const res = await fetch(this.attemptUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ answer: parseInt(this.answer, 10) }),
                });
                const data = await res.json();
                this.feedback = data;
                this.totalPoints = data.session_total_points ?? this.totalPoints;
                this.timeRemaining = data.time_remaining_seconds ?? this.timeRemaining;
                setTimeout(() => {
                    if (data.session_over) { this.goToSummary(); } else { this.fetchNext(); }
                }, 1400);
            },
            goToSummary() {
                clearInterval(this.timerInterval);
                window.location.href = this.summaryUrl;
            },
        }"
        class="w-full max-w-sm"
    >
        <div class="flex items-center justify-between text-orange-700 font-semibold mb-3 px-1">
            <span>⭐ <span x-text="totalPoints"></span> {{ __('Punkte') }}</span>
            <span>⏱ <span x-text="minutes()"></span>:<span x-text="seconds()"></span></span>
        </div>

        <div class="bg-white rounded-3xl shadow-xl p-8 text-center relative overflow-hidden">

            <template x-if="!feedback">
                <div>
                    <div class="text-5xl font-extrabold mb-6 h-16 flex items-center justify-center" x-text="loading ? '…' : prompt"></div>
                    <div class="text-4xl font-mono tracking-widest mb-6 h-12 flex items-center justify-center border-b-4 border-orange-200" x-text="answer || '?'"></div>

                    <div class="grid grid-cols-3 gap-3">
                        <template x-for="digit in [1,2,3,4,5,6,7,8,9]" :key="digit">
                            <button type="button" @click="press(digit)" class="bg-amber-50 hover:bg-amber-100 text-2xl font-bold rounded-2xl py-4 active:scale-95 transition">
                                <span x-text="digit"></span>
                            </button>
                        </template>
                        <button type="button" @click="backspace()" class="bg-gray-100 hover:bg-gray-200 text-xl font-bold rounded-2xl py-4 active:scale-95 transition">⌫</button>
                        <button type="button" @click="press(0)" class="bg-amber-50 hover:bg-amber-100 text-2xl font-bold rounded-2xl py-4 active:scale-95 transition">0</button>
                        <button type="button" @click="submit()" class="bg-green-500 hover:bg-green-600 text-white text-xl font-bold rounded-2xl py-4 active:scale-95 transition">✓</button>
                    </div>
                </div>
            </template>

            <template x-if="feedback">
                <div class="py-6">
                    <div class="text-6xl mb-3" x-text="feedback.is_correct ? '🎉' : '🤔'"></div>
                    <div class="text-2xl font-bold" x-text="feedback.is_correct ? '{{ __('Richtig!') }}' : '{{ __('Fast!') }}'"></div>
                    <div class="text-gray-500 mt-2" x-show="!feedback.is_correct">
                        {{ __('Antwort war') }}: <span class="font-semibold" x-text="feedback.correct_answer"></span>
                    </div>
                    <div class="text-orange-600 font-semibold mt-3" x-show="feedback.points_awarded > 0">
                        +<span x-text="feedback.points_awarded"></span> {{ __('Punkte') }}
                    </div>
                </div>
            </template>
        </div>

        <form method="POST" action="{{ route('child.sessions.finish', $session) }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="text-sm text-gray-400 underline">{{ __('Fertig für heute') }}</button>
        </form>
    </div>
</x-child-layout>
