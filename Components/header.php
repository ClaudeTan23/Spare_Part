<?php 
// require_once("./Modules/security.php");
?>

<div class="h-16 bg-slate-600 text-zinc-300 flex items-center justify-between px-4 py-2">
    <a href="./" class="font-bold text-2xl p-2">Spare Part Inventory</a>
        <div class="flex gap-4">
            <!-- <a href="#a">
                <div class="font-medium text-lg hover:bg-slate-500 hover:cursor-pointer rounded-lg p-2">Testing</div>
            </a>
            <a href="#a">
                <div class="font-medium text-lg hover:bg-slate-500 hover:cursor-pointer rounded-lg p-2">Testing</div>
            </a>
            <a href="#a">
                <div class="font-medium text-lg hover:bg-slate-500 hover:cursor-pointer rounded-lg p-2">Testing</div>
            </a> -->
            <?php if(!empty($_SESSION["spare_part"]["id"]) && !empty($user)) { ?>
                <div class="relative inline-block text-left">
                    <div>
                        <button type="button" id="user-selections" class="inline-flex w-full justify-between rounded-md px-4 py-2 text-medium font-medium text-zinc-300 shadow-sm focus:outline-none">
                            <?=$user["employeeID"]?>
                            <svg class="-mr-1 ml-2 h-5 w-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-gray-300 focus:outline-none hidden" id="user-cols">
                        <div class="py-1">
                            <a href="./profile.php" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Profile</a>
                            <a href="./logout.php" class="block px-4 py-2 text-sm font-medium text-gray-700 hover:text-red-500 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<script>
    const userBtn = document.getElementById("user-selections");

    if(userBtn !== null && userBtn !== undefined)
    {
        userBtn.addEventListener('click', () =>
        {
            document.getElementById('user-cols').classList.toggle('hidden');
        });
    }

    document.addEventListener("click", (event) =>
    {
        if(event.target !== userBtn) document.getElementById('user-cols').classList.add('hidden');
    });
</script>