<?php

namespace sergks\transliterate;

use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;
use yii\validators\UniqueValidator;

/**
 * BlameBehavior автоматически выполняет транслитерацию заданного значения.
 * Не забудьте указать кодировку mb-функций, mb_internal_encoding('utf-8').
 *
 * Пример,
 *
 * ```php
 * use sergks\transliterate\TransliterateBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => TransliterateBehavior::className(),
 *             'fromAttribute' => 'title',
 *             'toAttribute' => 'alias',
 *         ]
 *     ];
 * }
 * ```
 *
 * @author sergKs <serg31ks@yandex.ru>
 */
class TransliterateBehavior extends AttributeBehavior
{
    /**
     * Имя атрибута для которого получается значение транслитерированной строки
     * @var string
     */
    public $toAttribute = 'alias';

    /**
     * Имя атрибута из каторого получается значение транслитерированной строки
     * @var string
     */
    public $fromAttribute = 'title';

    /**
     * Валидация на уникальность
     * @var bool
     */
    public $validateUnique = true;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_BEFORE_INSERT => $this->toAttribute,
                BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->toAttribute
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function getValue($event)
    {
        if (strlen($this->owner->{$this->toAttribute}) === 0) {
            $value = $this->transliterateUrl($this->owner->{$this->fromAttribute});
        } else {
            $value = $this->owner->{$this->toAttribute};
        }

        if (strlen($value) > 0) {
            return $this->validateUnique ? $this->makeUniqueValue($value) : $value;
        } else {
            return $value;
        }
    }

    /**
     * Генерирует уникальное значение и возвращает его
     *
     * @param string $value
     * @return string
     */
    protected function makeUniqueValue($value)
    {
        $uniqueValue = $value;
        $index = 1;
        while (!$this->validateValue($uniqueValue)) {
            $uniqueValue .= '-' . $index++;
        }

        return $uniqueValue;
    }

    /**
     * Возвращает true при успешной валидации на уникальность, иначе false.
     *
     * @param string $value
     * @return bool
     */
    protected function validateValue($value)
    {
        /* @var $validator UniqueValidator */
        /* @var $model BaseActiveRecord */
        $validator = new UniqueValidator();
        $model = clone $this->owner;
        $model->clearErrors();
        $model->{$this->toAttribute} = $value;
        $validator->validateAttribute($model, $this->toAttribute);

        return !$model->hasErrors();
    }

    /**
     * Транслитерация текста
     *
     * @param string $text
     * @return string
     */
    protected function transliterateUrl($text)
    {
        $table = [
            // Буквосочетания
            'ий' => 'iy',
            'ый' => 'yi',
            'ье' => 'ye',
            'ьё' => 'yo',

            // Строчные буквы
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'e',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'й' => 'y',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sch',
            'ь' => '',
            'ы' => 'y',
            'ъ' => '',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',

            // Прописные буквы
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'E',
            'Ж' => 'Zh',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'Y',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'C',
            'Ч' => 'Ch',
            'Ш' => 'Sh',
            'Щ' => 'Sch',
            'Ь' => '',
            'Ы' => 'Y',
            'Ъ' => '',
            'Э' => 'E',
            'Ю' => 'Yu',
            'Я' => 'Ya',
            'q' => 'Q',
            ' ' => '-',
        ];
        $text = preg_replace(
            ['/[^a-zA-Z0-9\-]/u', '/[-]{2,}/u'],
            ['-', '-'],
            str_replace(array_keys($table), array_values($table), mb_strtolower($text))
        );
        return trim($text, '-');
    }
}