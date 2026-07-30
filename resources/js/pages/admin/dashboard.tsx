import { Head } from '@inertiajs/react';
import {
    Activity,
    Cloud,
    Database,
    KeyRound,
    Route,
    ShieldCheck,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Props = {
    system: {
        api_version: string;
        database: string;
        storage: 'configured' | 'pending';
        queue: string;
    };
    roles: string[];
};

export default function Dashboard({ system, roles }: Props) {
    const roleLabels: Record<string, string> = {
        'super-admin': '超级管理员',
        administrator: '管理员',
    };
    const databaseLabels: Record<string, string> = {
        pgsql: 'PostgreSQL',
        mysql: 'MySQL',
        sqlite: 'SQLite',
        sqlsrv: 'SQL Server',
    };
    const queueLabels: Record<string, string> = {
        database: '数据库队列',
        redis: 'Redis 队列',
        sync: '同步执行',
    };
    const statusCards = [
        {
            title: '客户端 API',
            value: system.api_version.toUpperCase(),
            description: '/api/v1 已启用',
            icon: Route,
        },
        {
            title: '数据库',
            value: databaseLabels[system.database] ?? system.database,
            description: '业务数据持久化',
            icon: Database,
        },
        {
            title: 'Cloudflare R2',
            value: system.storage === 'configured' ? '已配置' : '待配置',
            description: '私有业务文件存储',
            icon: Cloud,
        },
        {
            title: '队列',
            value: queueLabels[system.queue] ?? system.queue,
            description: '异步任务执行通道',
            icon: Activity,
        },
    ];

    return (
        <>
            <Head title="管理控制台" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="overflow-hidden rounded-3xl border bg-[linear-gradient(135deg,var(--sidebar-accent)_0%,var(--background)_52%,rgba(16,185,129,0.12)_100%)] p-6 shadow-sm">
                    <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div className="max-w-2xl space-y-3">
                            <Badge variant="secondary" className="rounded-full">
                                CareNote 管理后台
                            </Badge>
                            <div>
                                <h1 className="text-3xl font-semibold tracking-tight md:text-4xl">
                                    管理控制台
                                </h1>
                                <p className="mt-3 text-sm leading-6 text-muted-foreground md:text-base">
                                    API、权限和基础设施骨架已分层，后续业务模块将在这里逐步接入。
                                </p>
                            </div>
                        </div>

                        <div className="rounded-2xl border bg-background/75 p-4 backdrop-blur">
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <KeyRound className="size-4" />
                                当前角色
                            </div>
                            <div className="mt-2 flex flex-wrap gap-2">
                                {roles.map((role) => (
                                    <Badge key={role}>
                                        {roleLabels[role] ?? role}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {statusCards.map((item) => (
                        <Card key={item.title}>
                            <CardHeader className="flex-row items-start justify-between space-y-0">
                                <div>
                                    <CardDescription>
                                        {item.title}
                                    </CardDescription>
                                    <CardTitle className="mt-3 text-2xl capitalize">
                                        {item.value}
                                    </CardTitle>
                                </div>
                                <div className="rounded-2xl bg-primary/10 p-3 text-primary">
                                    <item.icon className="size-5" />
                                </div>
                            </CardHeader>
                            <CardContent className="text-sm text-muted-foreground">
                                {item.description}
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-3">
                            <div className="rounded-2xl bg-emerald-500/10 p-3 text-emerald-600">
                                <ShieldCheck className="size-5" />
                            </div>
                            <div>
                                <CardTitle>框架阶段</CardTitle>
                                <CardDescription className="mt-1">
                                    当前只提供身份隔离、RBAC、API v1
                                    和管理端页面骨架。
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="text-sm leading-6 text-muted-foreground">
                        家庭、药品、计划、记录和健康数据将在后续迁移阶段按领域接入，不在本阶段创建空业务模块。
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
