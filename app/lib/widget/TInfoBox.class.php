<?php
namespace eventos\Widget;

use Adianti\Widget\Base\TElement;

class TInfoBox extends TElement
{
    public function __construct($color, $faicon, $title, $number, $progress = 0, $increase = '', $textColor = 'white')
    {
        parent::__construct('div');
        $this->{'class'} = 'col-md-3 col-sm-6 col-xs-12';

        $progress = is_numeric($progress) ? $progress : 0;

        $div1 = new TElement('div');
        $div1->{'class'} = "info-box bg-{$color}";
        $div1->{'role'} = 'progressbar';

        $span = new TElement('span');
        $span->{'class'} = 'info-box-icon';
        $i = new TElement('i');
        $i->{'class'} = $faicon;
        $span->add($i);
        $div1->add($span);

        $div2 = new TElement('div');
        $div2->{'class'} = 'info-box-content';

        $span2 = new TElement('span');
        $span2->{'class'} = 'info-box-text';
        $span2->{'style'} = "color: {$textColor}";
        $span2->add($title);

        $span3 = new TElement('span');
        $span3->{'class'} = 'info-box-number';
        $span3->{'style'} = "color: {$textColor}";
        $span3->add($number);

        $div2->add($span2);
        $div2->add($span3);

        if ($progress > 0) {
            $div3 = new TElement('div');
            $div3->{'class'} = 'progress';

            $div4 = new TElement('div');
            $div4->{'class'} = 'progress-bar';
            $div4->{'style'} = "width: {$progress}%";

            $div3->add($div4);
            $div2->add($div3);
        }

        if (!empty($increase)) {
            $span4 = new TElement('span');
            $span4->{'class'} = 'progress-description';
            $span4->add($increase);
            $div2->add($span4);
        }

        $div1->add($div2);
        parent::add($div1);
    }
}
