document.getElementById('selection-stations').addEventListener('click', () =>
{
    document.getElementById('stations').classList.toggle('hidden');

});

document.getElementById('selection-cols').addEventListener('click', () =>
{
    document.getElementById('cols').classList.toggle('hidden');
});

document.addEventListener("click", (event) =>
{
    const stationsBtn = document.getElementById('selection-stations');
    const colsBtn = document.getElementById('selection-cols');

    const stationSpan = document.getElementById("station-span");
    const colSpan = document.getElementById("col-span");

    if(event.target.getAttribute("issvg") === "true") return;

    if(event.target !== stationsBtn && event.target !== stationSpan) document.getElementById('stations').classList.add('hidden');
    
    if(event.target !== colsBtn && event.target !== colSpan) document.getElementById('cols').classList.add('hidden');

});

document.getElementById("search").addEventListener("keyup", (event) =>
{
    if(event.key === "Enter") searchQuery();
});

if(document.getElementById("modal") !== null)
{
    document.getElementById("modal").onsubmit = async (e) =>
    {
        e.preventDefault();
        
        const elements = document.getElementById("modal").elements;
        const tableName = elements["tablename"].value;
        const columns = elements["cols"];
        const cols = [];
        let isDuplicated = false;
        const checkImages = elements["col-set-image"];
        const setImages = [];

        if(columns.length > 1)
        {
            for(let i = 0; i < columns.length; i++) 
            {
                if(!cols.includes(columns[i].value))
                {
                    cols.push(columns[i].value);
                    setImages.push(checkImages[i].checked);

                } else 
                {
                    isDuplicated = true;
                    alert("Can't have duplicate column name");
                    return;
                }
            };

        } else 
        {
            cols.push(elements["cols"].value);
            setImages.push(checkImages.checked);
        }


        if(isDuplicated) return;

        const form = new FormData();
        form.append("create_table", JSON.stringify({ tableName: tableName, tableColumns: cols, colSetImages: setImages }));
   
        const promise = await fetch("./Modules/API/tables.php", 
        {
            method: "POST",
            body: form
        });

        if(promise.ok)
        {
            const res = await promise.json();

            alert(res.msg);

            if(res.result === true) window.location.reload();

        } else 
        {
            alert(`Failed, status: ${await promise.statusText()}`);
        }
    }
}

const searchQuery = () =>
{
    const table = document.getElementById("station-span").textContent.trim();
    const column = document.getElementById("col-span").textContent.trim();
    const startDate = document.getElementById("startDate").value;
    const endDate = document.getElementById("endDate").value;
    const val = document.getElementById("search").value.trim();

    const dateParameters = (startDate !== "" && endDate !== "") ? `&startDate=${startDate}&endDate=${endDate}` : "";
 
    if(table !== "" && val !== "" && column !== "") window.location.href = `./?table=${table}&col=${column}&val=${val}${dateParameters}`;
        
    if(table !== "" && val == "") window.location.href = `./?table=${table}${dateParameters}`;
}

const addColumn = () =>
{
    const container = document.getElementById("add-cols-container");
    let num = container.children.length;

    const element = document.createElement("div");
    element.className = "flex flex-col gap-2 py-1";
    element.innerHTML = `<div class="flex justify-between items-end">
                            <label for="col${num+1}" class="font-semibold">Column ${num+1}</label>
                            <div class="flex justify-end gap-2">
                                <div class=" gap-1 flex items-center">
                                    <span class=" font-semibold">Set As Image: </span>
                                    <input type="checkbox" name="col-set-image" class="cursor-pointer form-checkbox h-5 w-5 text-blue-600" />
                                </div>
                                <button type="button" onclick="deleteColumn(event)" class="px-4 py-2 rounded bg-red-500 text-white text-sm">Delete Column</button>
                            </div>
                        </div>
                        <input type="text" id=col${num+1} placeholder="Column Name" name="cols" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />`;

    container.appendChild(element);
}

const addNewColumn = () =>
{
    const container = document.getElementById("edit-table-cols");
    let num = container.children.length;

    const element = document.createElement("div");
    element.className = "flex flex-col gap-2 py-1";
    element.innerHTML = `<div class="flex justify-between items-end">
                            <label for="edit-table-${num+1}" class="font-semibold">Column ${num+1}</label>
                            <div class=" gap-1 flex items-center">
                                <span class=" font-semibold">Set As Image: </span>
                                <input type="checkbox" name="edit-col-set-image" class="cursor-pointer form-checkbox h-5 w-5 text-blue-600" />
                                <button type="button" onclick="deleteEditColumn(event)" class="px-4 py-2 rounded bg-red-500 text-white text-sm">Delete Column</button>
                            </div>
                        </div>
                        <input id="edit-table-${num+1}" existed="no" autocomplete="off" name="edit-col-name" required class=" max-h-[150px] w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />`;

    container.appendChild(element);
}

const deleteTableColumn = async (e, columnsName, table) =>
{
    e.preventDefault();

    const confirmation = confirm(`Are you sure to delete ${columnsName}?`);

    if(!confirmation) return;

    const form = new FormData();

    form.append("delete_column", "delete_column");
    form.append("tableName", table);
    form.append("column", columnsName);

    const promise = await fetch("./Modules/API/tables.php", 
    {
        method: "POST",
        body: form
    });
    
    if(!promise.ok)
    {
        alert("Failed to delete table column"); 

    } else 
    {
        const jsonRes = await promise.json();

        if(jsonRes.result === false) 
        {
            alert(jsonRes.msg);
            return 

        } else 
        {
            alert(jsonRes.msg);
            
            const row = e.target.parentElement;
            row.remove();
        
            const container = document.getElementById("edit-table-cols")
            const num = container.children.length;

            for(let i = 0; i < num; i++)
            {
                container.children[i].children[0].getElementsByTagName("label")[0].setAttribute("for", `col${i+1}`);
                container.children[i].children[0].getElementsByTagName("label")[0].textContent = `Column ${i+1}`;
                container.children[i].getElementsByTagName("input")[0].setAttribute("id", `col${i+1}`);
            }
        }
    }
}

const updateTable = async (e, tableName) =>
{
    e.preventDefault();

    const inputs = document.getElementsByName("edit-col-name");

    const existedCol = [];
    const existedVal = [];
    const columns = [];

    for(let i = 0; i < inputs.length; i++)
    {
        const col = inputs[i];

        if(col.getAttribute("existed") == "yes")
        {
            existedCol.push(col.getAttribute("col-name"));
            existedVal.push(col.value.trim());

        } else 
        {
            columns.push(col.value.trim());
        }
    }

    const checkType = [];

    document.getElementsByName("edit-col-set-image").forEach(val => checkType.push(val.checked));
    
    const form = new FormData();
    form.append("update_table", "update_table");
    form.append("columns", JSON.stringify({ existedColNames: existedCol, existedVals: existedVal, newColumns: columns, table: tableName, checkType: checkType }));

    const promise = await fetch("./Modules/API/tables.php", 
    {
        method: "POST",
        body: form
    });

    if(!promise.ok)
    {
        alert("Failed to update the table");
        return;
    }

    const jsonRes = await promise.json();

    if(jsonRes.result === false)
    {
        alert(jsonRes.msg);
        return

    } else 
    {
        alert(jsonRes.msg);
        window.location.href = `./?table=${tableName}`;
    }

    
}

const deleteColumn = (event) =>
{
    const row = event.target.parentElement.parentElement.parentElement;
    row.remove();

    const container = document.getElementById("add-cols-container")
    const num = container.children.length;

    for(let i = 0; i < num; i++)
    {
        container.children[i].getElementsByTagName("label")[0].setAttribute("for", `col${i+1}`);
        container.children[i].getElementsByTagName("label")[0].textContent = `Column ${i+1}`;
        container.children[i].getElementsByTagName("input")[1].setAttribute("id", `col${i+1}`);
    }
    
}

const deleteEditColumn = (event) =>
    {
        const row = event.target.parentElement.parentElement.parentElement;
        console.log(row);
        row.remove();
    
        const container = document.getElementById("edit-table-cols")
        const num = container.children.length;

        for(let i = 0; i < num; i++)
        {
            container.children[i].getElementsByTagName("label")[0].setAttribute("for", `col${i+1}`);
            container.children[i].getElementsByTagName("label")[0].textContent = `Column ${i+1}`;
            container.children[i].getElementsByTagName("input")[0].setAttribute("id", `col${i+1}`);
        }
        
    }

const toggleModal = () => 
{
    const modal = document.getElementById('modal');

    if(!modal.classList.contains("hidden"))
    {
        modal.children[0].remove();

    } else 
    {
        modal.innerHTML = 
        `<div class="bg-white rounded-lg shadow-lg w-[800px] max-w-[820px]:w-[500px] mx-auto p-6 max-h-[90vh] overflow-y-auto animate-popsUp">
            <div class="flex justify-between items-start">
                <h1 class="text-2xl font-semibold mb-4 px-4 py-2">Create Table</h1> 
                <button type="button" class="px-4 py-2 rounded bg-slate-700 text-white" onclick="addColumn()">Add Column</button>
            </div>
            <div class="flex flex-col gap-2 py-1">
                <label for="tableName" class="font-semibold">Table Name</label>
                <input type="text" id="tableName" placeholder="Table Name" name="tablename" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />
            </div>
            <div id="add-cols-container">
                <div class="flex flex-col gap-2 py-1">
                    <div class="flex justify-between items-end">
                        <label for="col1" class="font-semibold">Column 1</label>
                        <div class="flex justify-end gap-2">
                            <div class=" gap-1 flex items-center">
                                <span class=" font-semibold">Set As Image: </span>
                                <input type="checkbox" name="col-set-image" class="cursor-pointer form-checkbox h-5 w-5 text-blue-600" />
                            </div>
                        </div>
                    </div> 
                    <input type="text" id="col1" placeholder="Column Name" name="cols" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />
                </div>
            </div>

            <div class="flex justify-between pt-3">
                <button type="submit" name="create_table" class="bg-green-600 text-white px-4 py-2 rounded w-5/12">Create</button>
                <button type="button" onclick="toggleModal()" class="bg-red-500 text-white px-4 py-2 rounded w-5/12">Close</button>
            </div>
        </div>`;
    }

    modal.classList.toggle('hidden');
}

const toggleEditModal = async (table) => 
{
    const modal = document.getElementById('modal-edit-table');
  
    if(!modal.classList.contains("hidden"))
    {
        modal.children[0].remove();

    } else 
    {
        const promise = await fetch(`./Modules/API/tables.php?get_table_columns=${table}`);

        if(!promise.ok) 
        {
            alert("Fail to fetch table");
            return;
        }

        const { columns, columnsType } = await promise.json();

        let elem = ``;
        let num = 1;
       
        columns.forEach(column => 
        {
            elem += `
                <div class="flex flex-col gap-2 py-1"> 
                    <form action="./Modules/API/tables.php" method="POST" class="flex flex-col gap-2 py-1" onsubmit="deleteTableColumn(event, '${column}', '${table}')">
                        <div class="flex justify-between items-end">
                            <label for="${column}" class="font-semibold">Column ${num}:</label>
                            ${num === 1 ? '' : 
                            `
                            <div class="gap-1 flex items-center hidden">
                                <span class=" font-semibold">Set As Image: </span>
                                <input type="checkbox" ${columnsType[num-1] === "longtext" ? `checked` : `` } name="edit-col-set-image" class="cursor-pointer form-checkbox h-5 w-5 text-blue-600" />
                            </div>
                            <button type="submit" class="px-4 py-2 rounded bg-red-500 text-white text-sm">Delete Column</button>
                            `}
                        </div>
                        ${num === 1 ? `<input type="text" existed="yes" id="${column}"  placeholder="${column}" readonly value="${column}" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />` 
                            : `<input type="text" existed="yes" id="${column}"  placeholder="${column}" col-name="${column}" name="edit-col-name" value="${column}" required class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" />`
                        }
                    </form>
                </div>`;
            
            num++;
     
        });

        modal.innerHTML = 
        `<div class="bg-white rounded-lg shadow-lg w-[800px] max-w-[820px]:w-[500px] mx-auto p-6 max-h-[90vh] overflow-y-auto animate-popsUp">
            <div class="flex justify-between items-start">
                <h1 class="text-2xl font-semibold mb-4 px-4 py-2">Edit Table</h1>
                <button type="button" class="px-4 py-2 rounded bg-slate-700 text-white" onclick="addNewColumn()">Add New Column</button>
            </div>
            <div id="edit-table-cols">
                ${elem}
            </div>

            <div class="flex justify-between pt-3">
                <button type="submit" name="create_table" class="bg-green-600 text-white px-4 py-2 rounded w-5/12">Update Table</button>
                <button type="button" onclick="toggleEditModal()" class="bg-red-500 text-white px-4 py-2 rounded w-5/12">Close</button>
            </div>
        </div>`;


    }

    modal.classList.toggle('hidden');
}

const toggleAddRowModal = () =>
{
    const modal = document.getElementById("modal-add-row");
    

    if(!modal.classList.contains("hidden"))
    {
        const inputs = modal.getElementsByTagName("textarea");
        
        for(let i = 0; i < inputs.length; i++)
        {
            inputs[i].value = "";
        }
    
    } 
    
    modal.classList.toggle('hidden');
}

const toggleEditTableModal = () =>
{
    const modal = document.getElementById("modal-edit-table");
    
    if(!modal.classList.contains("hidden"))
    {
        const inputs = modal.getElementsByTagName("textarea");
            
        for(let i = 0; i < inputs.length; i++)
        {
            inputs[i].value = "";
        }
        
    } 
        
    modal.classList.toggle('hidden');
}

document.getElementById("modal-add-row").onsubmit = async (e) =>
{
    e.preventDefault();
   
    const elem = document.getElementById("modal-add-row").elements;
    const colNames = [];
    const colValues = [];
    const tableName = elem["table_name"].value.trim();
    
    const colNameImg = [];
    const colValueImg = [];

    const form = new FormData();

    const AddRowRequest = async () =>
    {
        form.append("add_row", JSON.stringify({ colNames: colNames, colValues: colValues, tableName: tableName, imgColNames: colNameImg }));
        
        const promise = await fetch("./Modules/API/tables.php",
        {
            method: "POST",
            body: form
        });

        if(promise.ok)
        {
            const jsonRes = await promise.json();

            alert(jsonRes.msg);

            if(jsonRes.result === true) window.location.href = `./?table=${tableName}`;

        } else 
        {
            alert(`Failed, status: ${await promise.statusText()}`);
        }
    }
    
    if(elem["colName"] !== undefined)
    {
        if(elem["colName"].length > 1)
        {
            elem["colName"].forEach(val => colNames.push(val.value.trim()));
            elem["colValue"].forEach(val => colValues.push(val.value.trim()));

        } else 
        {
            colNames.push(elem["colName"].value.trim());
            colValues.push(elem["colValue"].value.trim());
        }
    } 

    if(elem["colNameImg"] !== undefined)
    {
        if(elem["colNameImg"].length > 1)
        {
            for(let i = 0; i < elem["colNameImg"].length; i++) 
            {
                if(elem["image"][i].files[0] === undefined) 
                {
                    colNames.push(elem["colNameImg"][i].value.trim());
                    colValues.push("-");
                    continue;
                }

                colNameImg.push(elem["colNameImg"][i].value.trim());
                form.append("image[]", elem["image"][i].files[0])
                
            }

            await AddRowRequest();

        } else 
        {
            if(elem["image"].files[0] == undefined)
            {
                colNames.push(elem["colNameImg"].value.trim());
                colValues.push("-");

            } else 
            {
                colNameImg.push(elem["colNameImg"].value.trim());
                form.append("image[]", elem["image"].files[0])
            }

            await AddRowRequest();

        }

    } else 
    {
        await AddRowRequest();
    }

}

const deleteTable = (table) =>
{
    const confirmation = confirm(`Are you sure, for delete table "${table}"?`)
    const deleteForm = document.getElementById("deleteForm").elements;

    if(!confirmation) return;

    deleteForm["delete_table"].click();
}

const deleteRow = (rowID) =>
{
    const confirmation = confirm("Are you sure to delete this row?");

    if(!confirmation) return;

    document.getElementById(`row-delete-${rowID}`).click();
}

const editFetchRow = async (rowId, table, colName) =>
{
    const promise = await fetch(`./Modules/API/tables.php?editRowId=${rowId}&idColumnName=${colName}&editTableName=${table}`);

    if(promise.ok)
    {
        const jsonRes = await promise.json();

        if(jsonRes.result == false) 
        {
            alert(jsonRes.msg);
            return;
        }

        const editModal = document.getElementById("modal-edit-row");

        editModal.classList.remove('hidden');

        const columnNames = jsonRes.columns;
        const columnValues = jsonRes.values;
        const columnType = jsonRes.types;

        columnNames.splice(jsonRes.columns.length - 3, 3)
        columnValues.splice(jsonRes.values.length - 3, 3);

        let colsElem = ``;

        for(let i = 1; i < columnType.length; i++)
        {
            colsElem +=
            `<div>
                    <div class="flex flex-col gap-2 py-1">
                        <label for="editColValue-${i}" class="font-semibold">${columnNames[i]}:</label>
                        <input type="text" hidden name="editColName" value="${columnNames[i]}"/>
                    </div>
                    ${columnType[i] === "longtext" ? 
                        `
                        <div class="pb-3 ">
                            ${columnValues[i] === "-" ?
                            `<span>-</span>`
                            :
                            `<img src="./static/img/${columnValues[i]}" class="w-56 h-40 object-fit rounded-lg shadow-lg transition-transform duration-300 ease-in-out transform hover:scale-110 cursor-pointer hover:z-0" >`
                            }
                        </div>
                        <div class="flex gap-4">
                            <label for="edit-image-${i}" class="flex text-white px-4 py-2 rounded w-5/12 bg-slate-600 justify-center gap-2 cursor-pointer shadow-md">
                            <span>Edit Attachment</span>
                                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 16 5-7 6 6.5m6.5 2.5L16 13l-4.286 6M14 10h.01M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/>
                                </svg>
                            </label>
                            <button type="button" class="bg-red-500 text-white px-4 py-2 rounded w-5/12 flex gap-2 justify-center ${columnValues[i] == "-" ? "hidden" : ""}" onclick="editRemoveImage(event)">Delete Attachment
                                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m3 16 5-7 6 6.5m6.5 2.5L16 13l-4.286 6M14 10h.01M4 19h16a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"/>
                                </svg>
                            </button>
                        </div>
                        <textarea id="editColValue-${i}" hidden data-type="${columnType[i]}" autocomplete="off" name="edit-${columnNames[i]}" columnName="${columnNames[i]}" required class=" max-h-[150px] w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium">${columnValues[i]}</textarea>
                        <input type="file" id="edit-image-${i}" name="image" hidden id="add-img" oninput="editImages(event, ${i})" accept="image/*"/>
                        `
                        :
                        `
                        ${columnNames[i] == "Minimum Quantity" || columnNames[i] == "Current Quantity" ?
                           `<input type="number" id="editColValue-${i}" data-type="${columnType[i]}" placeholder="${columnValues[i]}" autocomplete="off" name="${columnNames[i] == "Minimum Quantity" ? "minQty" : "curQty" }" columnName="${columnNames[i]}" required class=" max-h-[150px] w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium" value="${columnValues[i]}" />`
                           :
                           `<textarea id="editColValue-${i}" data-type="${columnType[i]}" placeholder="${columnValues[i]}" autocomplete="off" name="edit-${columnNames[i]}" columnName="${columnNames[i]}" required class=" max-h-[150px] w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 text-medium">${columnValues[i]}</textarea>`
                        }
                        `
                    }
            </div>
            `
        }

        editModal.children[0].innerHTML = 
                `<div class="flex justify-between items-start">
                    <h1 class=" text-2xl font-bold mb-4 py-2">Edit Row</h1>
                    <input type="text" hidden name="table_name" value="${table}" />
                </div>
            
                <div class="flex gap-2 py-1 flex-col">
                    <label for="editRowId" class="font-semibold">${columnNames[0]}: </label>
                    <input id="editRowId" name="edit-id" columnName="${columnNames[0]}" value="${columnValues[0]}" class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-medium focus:no-underline focus:bg-none" readonly />
                </div>

                ${colsElem}

                <div class="flex justify-between pt-3">
                    <button type="submit" name="edit_row" class="bg-green-600 text-white px-4 py-2 rounded w-5/12">Update Row</button>
                    <button type="button" onclick="(() => document.getElementById('modal-edit-row').classList.add('hidden'))()" class="bg-red-500 text-white px-4 py-2 rounded w-5/12">Close</button>
                </div>`

    } else 
    {
        alert(`Failed to fetch status code: ${promise.status}`);
    }
}


if(document.getElementById("modal-edit-row") !== null && document.getElementById("modal-edit-row") !== undefined)
{
    document.getElementById("modal-edit-row").onsubmit = async (e) =>
    {
        e.preventDefault();

        const txtAreas = document.getElementById("modal-edit-row").getElementsByTagName("textarea");
        const parameters = [];
        const keys = [];
        const values = [];
        const imageKeys = [];
        const tableName = document.getElementById("modal-edit-row").elements["table_name"].value.trim();
        const rowID = document.getElementById("modal-edit-row").elements["edit-id"].value.trim();
        const colIdName = document.getElementById("modal-edit-row").elements["edit-id"].getAttribute("columnName");
        const minQty = document.getElementById("modal-edit-row").elements["minQty"].value;
        const curQty = document.getElementById("modal-edit-row").elements["curQty"].value;
        const form = new FormData();

        keys.push("Minimum Quantity", "Current Quantity");
        values.push(minQty, curQty)
       
        const PostEditData = async (index) =>
        {
            if(index === txtAreas.length)
            {
                form.append("edit_row", JSON.stringify({ properties: keys, values: values, imageProperties: imageKeys }));
                form.append("tableName", tableName);
                form.append("rowID", rowID);
                form.append('colIdName', colIdName);

                const promise = await fetch("./Modules/API/tables.php", 
                {
                    method: "POST",
                    body: form
                });

                if(promise.ok)
                {
                    const jsonRes = await promise.json();

                    alert(jsonRes.msg);

                    if(jsonRes.result == false) return;

                    window.location.href = `./?table=${tableName}`;

                } else 
                {
                    alert(`Failed to fetch status code: ${promise.status}`);
                }

                return;
            }

            if(txtAreas[index].getAttribute("data-type") === "longtext")
            {
                const fileInput = txtAreas[index].parentElement.getElementsByTagName("input")["image"];
                
                if(fileInput.files.length > 0)
                {
             
                    form.append("image[]", fileInput.files[0]);
                    imageKeys.push(txtAreas[index].getAttribute("columnName"));
                    PostEditData(index+1);

                } else 
                {
                    const colName = txtAreas[index].getAttribute("columnName");
                    const value = txtAreas[index].value;
           
                    if(value === "-")
                    {
                        keys.push(colName);
                        values.push(value);
                    }

                    PostEditData(index+1);
                }

            } else 
            {
                const colName = txtAreas[index].getAttribute("columnName");
                const value = txtAreas[index].value;

                keys.push(colName);
                values.push(value);

                PostEditData(index+1);
            }
        }

        if(txtAreas.length > 0) PostEditData(0);

    } 
}

const addImage = (e, index) =>
{
    const image = e.target.files;
    const i = index - 1;

    if(image.length <= 0) 
    {
        if(!e.target.parentElement.parentElement.getElementsByTagName("img")[0].classList.contains("hidden"))
        {
            e.target.parentElement.parentElement.getElementsByTagName("img")[0].classList.add("hidden");
            e.target.parentElement.parentElement.getElementsByTagName("button")[0].classList.add("hidden");
            e.target.parentElement.parentElement.getElementsByTagName("img")[0].src = "";
        }
        return;
    }

    const imgElm = e.target.parentElement.parentElement.getElementsByTagName("img")[0];
    const rmBtn = e.target.parentElement.parentElement.getElementsByTagName("button")[0];

    const blob = URL.createObjectURL(image[0]);
    imgElm.classList.remove("hidden");
    imgElm.src = blob;

    rmBtn.classList.remove("hidden");
    
}

const removeImage = (e) =>
{
    const imgElm = e.target.parentElement.parentElement.getElementsByTagName("img");
    if(imgElm.length !== 1) return;
    
    const imgInput = e.target.parentElement.getElementsByTagName("input")[1];
    imgElm[0].src = "";
    imgElm[0].classList.add("hidden");
    imgInput.value = null;
    e.target.classList.add("hidden");
    
}

const editImages = (e, index) =>
{
    const image = e.target.files;
    const i = index - 1;
    let img;

    if(image.length <= 0) 
    {
        if(e.target.parentElement.getElementsByTagName("img").length > 0 && e.target.parentElement.getElementsByTagName("span").length <= 1)
        {
            const ogFilename = e.target.parentElement.getElementsByTagName("textarea")[0].value;
            const span = document.createElement("span");
            span.textContent = "-";
            
            if(ogFilename === "-")
            {
                e.target.parentElement.getElementsByTagName("div")[1].appendChild(span);
                e.target.parentElement.getElementsByTagName("button")[0].classList.add("hidden");
                e.target.parentElement.getElementsByTagName("img")[0].remove();

            } else
            {
                e.target.parentElement.getElementsByTagName("img")[0].src = `./static/img/${ogFilename}`;
            }
        
            e.target.value = null;
        }

        return; 
    }

    const rmBtn = e.target.parentElement.getElementsByTagName("button")[0];
    const blob = URL.createObjectURL(image[0]);

    rmBtn.classList.remove("hidden");
    
    if(e.target.parentElement.getElementsByTagName("span").length > 1) e.target.parentElement.getElementsByTagName("span")[0].remove();

    if(e.target.parentElement.getElementsByTagName("img").length > 0)
    {
        img = e.target.parentElement.getElementsByTagName("img")[0];
        img.src = blob;

    } else 
    {
        img = document.createElement("img");
        img.className = "w-56 h-40 object-fit rounded-lg shadow-lg transition-transform duration-300 ease-in-out transform hover:scale-110 cursor-pointer hover:z-0";
        img.src = blob;
        e.target.parentElement.getElementsByTagName("div")[1].appendChild(img);

    }
        
}

const editRemoveImage = (e) =>
{
    const imgElm = e.target.parentElement.parentElement.getElementsByTagName("img");

    if(imgElm.length !== 1) return;

    const imgInput = e.target.parentElement.parentElement.getElementsByTagName("input");
    imgInput[imgInput.length - 1].value = null;
    imgElm[0].remove();
    imgInput.value = null;
    e.target.classList.add("hidden");
    e.target.parentElement.parentElement.getElementsByTagName("textarea")[0].value = "-";

    if(e.target.parentElement.parentElement.getElementsByTagName("span").length <= 1)
    {
        const span = document.createElement("span");
        span.textContent = "-";
        e.target.parentElement.parentElement.getElementsByTagName("div")[1].appendChild(span);
    }
    
}

const displayImage = (e) =>
{
    const srcImg = e.target.src;
    const imgModal = document.createElement("div");
    imgModal.innerHTML = 
    `
    <div class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-10" id="imageModal">
        <svg fill="#000000" class="fixed top-0 right-0 cursor-pointer bg-white rounded-lg" height="50px" width="50px" onclick="(() => document.getElementById('imageModal').remove())()" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="-49 -49 588.00 588.00" xml:space="preserve" stroke="#000000" stroke-width="30" transform="matrix(-1, 0, 0, -1, 0, 0)rotate(0)"><g id="SVGRepo_bgCarrier" stroke-width="0" transform="translate(0,0), scale(1)"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="7.840000000000001"></g><g id="SVGRepo_iconCarrier"> <polygon points="456.851,0 245,212.564 33.149,0 0.708,32.337 212.669,245.004 0.708,457.678 33.149,490 245,277.443 456.851,490 489.292,457.678 277.331,245.004 489.292,32.337 "></polygon> </g></svg>
         <img src="${srcImg}" class=" rounded-lg shadow-xl">
    </div>
    `;

    document.body.appendChild(imgModal);
}