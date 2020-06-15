# Подключение
Добавить в composer пакеты "dragonmantank/cron-expression"(для php70 - версия 2.2.0), "psr/log" 
composer require dragonmantank/cron-expression:2.2.0
composer require psr/log
Добавить в cron запуск /local/modules/aniart.main/lib/cron/cli/tick.php каждую минуту

# Создание задачи
В каталоге /local/modules/aniart.main/lib/cron/tasks создать класс задачи, наследник от Aniart\Main\Cron\Lib\Models\AbstractTask  
Если предполагается большая задача, то создать свое пространство имен.  
В классе задачи необходимо реализовать метод run().

### Полезные методы
В классе задачи будут доступны методы:
- getName() - название(код) задачи
- getArguments() - массив аргументов, переданными при запуске задачи

В \Aniart\Main\Cron\Lib\Services\TaskService
TaskService::getInstance()->setProgress($task, $msg) - записать прогресс выполнения, если задача выполняется долго и есть необходимость отслеживать прогресс выполнения.
TaskService::getInstance()->getProgress($task) - получить прогресс выполнения.


# Настройка
Файл настроек /local/modules/aniart.main/lib/cron/config.php  

Константа TASK_LIST - массив с настройками для каждой задачи.  
<pre>
TASK_LIST = [
    'example_task_1' => [  
        'TITLE' => 'Задача-1',  
        'TASK' => Tasks\Example\Task1::class,
        'USER_ID' => 0,
        'LOW_PRIORITY' => true, // запустить с низким приоритетом по процессору и вводу-выводу
    ],
    [...],
    [...]
]
</pre>

Ключ TASK обязательный.  
Если нужно запустить одну задачу, то в ключе TASK указывается класс задачи.
<pre>'TASK' => Tasks\Example\Task1::class</pre>
Если нужно запустить последовательно несколько задач, то в ключе TASK указывается массив с идентификаторами.
<pre>'TASK' => ['example_task_1', 'task_2_Id', 'task_3_Id']</pre>
  
Ключ TITLE только для мониторинга.  
Ключ USER_ID - идентификатор пользователя с правами которого должна работать задача(или последовательность задач).


Константа CRONTAB - массив с настройками запуска задач по расписанию.
<pre>
CRONTAB = [
        '* * * * * example_task_1',
        '*/2 * * * * example_task_2 par1 par2 par3',
    ]
</pre>
Каждая строка соостит из времени запуска в формате crontab, идентификатора задачи, аргументов(если нужны).


# Запуск
1. По расписанию. В настройках в константу CRONTAB добавить запись.
2. Из консоли.<pre>
   php -f /app/local/modules/aniart.main/lib/cron/cli/starter.php <идентификатор задачи> <строка аргументов если нужны>
   php -f /app/local/modules/aniart.main/lib/cron/cli/starter.php example_task_2
   php -f /app/local/modules/aniart.main/lib/cron/cli/starter.php example_task_2 par1 par2 par3</pre>
3. Из кода.
   <pre>\Aniart\Main\Cron\Manager::addToRun('идентификатор задачи', 'строка аргументов если нужны');
   \Aniart\Main\Cron\Manager::addToRun('example_task_2');
   \Aniart\Main\Cron\Manager::addToRun('example_task_2', 'par1 par2 par3');</pre>  
   Метод addToRun() только отмечает, что задача требует запуска.  
   Попытка запуска каждой отмеченной задачи производится каждую минуту. 
   Если задача уже выполняется, то запуск будет отложен.
4. Из кода, для отладки. Попытка запустить задачу немедленно.  
   Если по каким-то причинам запуск отложен/пропущен, то больше не будет попыток запуска.
   <pre>
   \Aniart\Main\Cron\Manager::runTaskNow('идентификатор задачи', 'строка аргументов если нужны');
   \Aniart\Main\Cron\Manager::runTaskNow('example_task_2');
   \Aniart\Main\Cron\Manager::runTaskNow('example_task_2', 'par1 par2 par3');
   </pre>

# Мониторинг
- Выполняющиеся задачи видны в списке процессов(ps -aux), имя процесса - идентификатор задачи.
- На странице /_cronmonitor.php
- \Aniart\Main\Cron\Manager::getProgress(идентификатор задачи) - прогресс выполнения
- \Aniart\Main\Cron\Manager::getStatus(идентификатор задачи) - статус задачи (вернется объект TaskStatusService)
