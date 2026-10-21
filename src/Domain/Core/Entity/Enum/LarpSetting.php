<?php

namespace App\Domain\Core\Entity\Enum;

enum LarpSetting: string implements LabelableEnumInterface
{
    case FANTASY = 'fantasy';
    case SCI_FI = 'sci-fi';
    case CYBERPUNK = 'cyberpunk';
    case HISTORIC = 'history';
    case BATTLE = 'battle';
    case NOIR = 'noir';
    case POST_APO = 'postapo';
    case WITCHER = 'witcher';
    case HARRY_POTTER = 'harry_potter';

    public function getLabel(): string
    {
        return match ($this) {
            self::FANTASY => 'enum.larp_setting.fantasy',
            self::SCI_FI => 'enum.larp_setting.sci_fi',
            self::CYBERPUNK => 'enum.larp_setting.cyberpunk',
            self::HISTORIC => 'enum.larp_setting.historic',
            self::BATTLE => 'enum.larp_setting.battle',
            self::NOIR => 'enum.larp_setting.noir',
            self::POST_APO => 'enum.larp_setting.post_apo',
            self::WITCHER => 'enum.larp_setting.witcher',
            self::HARRY_POTTER => 'enum.larp_setting.harry_potter',
        };
    }
}
