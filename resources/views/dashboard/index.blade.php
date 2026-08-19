@extends('layouts.app')

@section('title', 'Главная')

@section('content')

    <h1 class="main-content__title">Главная</h1>

    <section class="main-content__section">
        <h2 class="main-content__section-title">Ключевые показатели склада</h2>

        <div class="main-content__stats">
            <div class="main-content__stat">
                <span class="main-content__stat-label">Материалы</span>
                <strong class="main-content__stat-value">{{ $materialsCount }}</strong>
            </div>

            <div class="main-content__stat">
                <span class="main-content__stat-label">Рулоны</span>
                <strong class="main-content__stat-value">{{ $rollsCount }}</strong>
            </div>

            <div class="main-content__stat">
                <span class="main-content__stat-label">Остаток</span>
                <strong class="main-content__stat-value">{{ $totalWeight }}
                    кг</strong>
            </div>
        </div>
    </section>

    {{-- Быстрые действия --}}
    <section class="main-content__section">
        <h2 class="main-content__section-title">Быстрые действия</h2>

        <a class="main-content__action"
                href="{{ route('material-receipts.create') }}">
            Оприходовать сырьё
        </a>
    </section>

    {{-- Последние операции --}}
    <section class="main-content__section">
        <h2 class="main-content__section-title">Последние операции</h2>

        @if ($recentReceipts->isEmpty())
            <p class="main-content__empty">Операций пока нет.</p>
        @else
            <div class="main-content__operations">
                @foreach ($recentReceipts as $receipt)
                    <article class="main-content__operation">
                        <div class="main-content__operation-header">
                            <strong class="main-content__operation-title">
                                Оприходование
                            </strong>

                            <time class="main-content__operation-date">
                                {{ $receipt->created_at->format('d.m.Y H:i') }}
                            </time>
                        </div>

                        <div class="main-content__operation-info">
                            <span class="main-content__operation-material">
                                {{ $receipt->material->name }}
                            </span>

                            <span class="main-content__operation-roll">
                                Рулон №{{ $receipt->roll->roll_number }}
                            </span>

                            <strong class="main-content__operation-weight">
                                {{ $receipt->weight }} кг
                            </strong>

                            <span class="main-content__operation-user">
                                {{ $receipt->user->name }}
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection