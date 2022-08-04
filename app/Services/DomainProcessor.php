<?php

namespace App\Services;

use App\Models\Domain;
use Illuminate\Support\Facades\Log;

class DomainProcessor
{

    private Domain $domain;

    public function __construct(Domain $domain) {
        $this->domain = $domain;
        $this->domain->loadMissing(['collaborator','prnews','prposting']);
    }

    //Заполнение данных основной таблицы из дочерних
    //Приоритет бирж Collaborator > Prnews > Prposting, но обработка происходит в обратном порядке когда более приоритетное значение перезаписываем менее приоритетное

    static public function process() : void
    {
        Log::channel('domainProcessor')->info('Fetching domains from database');
        $domains = Domain::query()->with(['collaborator','prnews','prposting'])->get();
        Log::channel('domainProcessor')->info('Domains fetched: '.$domains->count());

        $counter = 1;
        foreach ($domains as $domain) {

            $domainProcessor = new static($domain);

            //Сразу проверим домен на наличие непустых связей, если ни одной связи нету, такой домен можно удалить
            if (!array_filter($domain->getRelations())) {
                Log::channel('domainProcessor')->info('['.$counter.'/'.$domains->count().'] DELETE Domain => '.$domain->domain.' ');
                $domain->delete();
            } else {
                //Каждый показатель проходим отдельно, чтоб была возможность менять порядок бирж, в каждом апдейте порядок идет от менее приоритетному к более приоритетному, таким образом если есть более приоритетное значении оно перезапишет менее приоритеное
                Log::channel('domainProcessor')->info('['.$counter.'/'.$domains->count().'] UPDATE Domain => '.$domain->domain);
                $domainProcessor->updateTheme();
                $domainProcessor->updateCountry();
                $domainProcessor->updateTraffic();
                $domainProcessor->updateAhrefs();
                $domainProcessor->updateMajestic();
            }

            $counter++;
        }
    }

    public function updateTheme() : void
    {
        ($this->domain?->prposting?->theme !== null) ? $this->domain->theme = $this->domain->prposting->theme : null ;
        ($this->domain?->prnews?->theme !== null) ? $this->domain->theme = $this->domain->prnews->theme : null ;
        ($this->domain?->collaborator?->theme !== null) ? $this->domain->theme = $this->domain->collaborator->theme : null ;

        if ($this->domain->isDirty()) {
            $this->domain->save();
        }
    }

    public function updateTraffic() : void
    {
        ($this->domain?->prposting?->traffic !== null) ? $this->domain->traffic = $this->domain->prposting->traffic : null ;
        ($this->domain?->prnews?->traffic !== null) ? $this->domain->traffic = $this->domain->prnews->traffic : null ;
        ($this->domain?->collaborator?->traffic !== null) ? $this->domain->traffic = $this->domain->collaborator->traffic : null ;

        if ($this->domain->isDirty()) {
            $this->domain->traffic_updated_at = now();
            $this->domain->save();
        }
    }

    public function updateCountry() : void
    {
        ($this->domain?->prposting?->country !== null) ? $this->domain->country = $this->domain->prposting->country : null ;
        ($this->domain?->prnews?->country !== null) ? $this->domain->country = $this->domain->prnews->country : null ;
        ($this->domain?->collaborator?->country !== null) ? $this->domain->country = $this->domain->collaborator->country : null ;

        if ($this->domain->isDirty()) {
            $this->domain->save();
        }
    }

    public function updateAhrefs() : void
    {
        ($this->domain?->prposting?->dr !== null) ? $this->domain->ahrefs_dr = $this->domain->prposting->dr : null ;
        ($this->domain?->prnews?->dr !== null) ? $this->domain->ahrefs_dr = $this->domain->prnews->dr : null ;
        ($this->domain?->collaborator?->dr !== null) ? $this->domain->ahrefs_dr = $this->domain->collaborator->dr : null ;

        if ($this->domain->isDirty()) {
            $this->domain->ahrefs_updated_at = now();
            $this->domain->save();
        }
    }

    public function updateMajestic() : void
    {
        ($this->domain?->prposting?->cf !== null) ? $this->domain->majestic_cf = $this->domain->prposting->cf : null ;
        ($this->domain?->prnews?->cf !== null) ? $this->domain->majestic_cf = $this->domain->prnews->cf : null ;
        ($this->domain?->collaborator?->cf !== null) ? $this->domain->majestic_cf = $this->domain->collaborator->cf : null ;

        ($this->domain?->prposting?->tf !== null) ? $this->domain->majestic_tf = $this->domain->prposting->tf : null ;
        ($this->domain?->prnews?->tf !== null) ? $this->domain->majestic_tf = $this->domain->prnews->tf : null ;
        ($this->domain?->collaborator?->tf !== null) ? $this->domain->majestic_tf = $this->domain->collaborator->tf : null ;

        if ($this->domain->isDirty()) {
            $this->domain->majestic_updated_at = now();
            $this->domain->save();
        }
    }

}